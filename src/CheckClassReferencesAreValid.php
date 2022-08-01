<?php

namespace Imanghafoori\LaravelMicroscope;

use ErrorException;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Checks\CheckClassReferences;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\TokenAnalyzer\ClassReferenceFinder;
use Imanghafoori\TokenAnalyzer\ClassRefExpander;
use Imanghafoori\TokenAnalyzer\GetClassProperties;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class CheckClassReferencesAreValid
{
    public static function check($tokens, $absFilePath, $phpFilePath, $psr4Path, $psr4Namespace, $params)
    {
        try {
            return self::checkReferences($tokens, $absFilePath, $params);
        } catch (ErrorException $e) {
            // In case a file is moved or deleted,
            // composer will need a dump autoload.
            if (! Str::endsWith($e->getFile(), 'vendor\composer\ClassLoader.php')) {
                throw $e;
            }

            self::warnDumping($e->getMessage());
            resolve(Composer::class)->dumpAutoloads();
        }
    }

    public static function warnDumping($msg)
    {
        $p = resolve(ErrorPrinter::class)->printer;
        $p->writeln('It seems composer has some trouble with autoload...');
        $p->writeln($msg);
        $p->writeln('Running "composer dump-autoload" command...  \(*_*)\  ');
    }

    private static function checkReferences($tokens, $absFilePath, $imports)
    {
        // If file is empty or does not begin with <?php
        if (($tokens[0][0] ?? null) !== T_OPEN_TAG) {
            return;
        }

        [
            $currentNamespace,
            $class,
            $type,
            $parent,
            $interfaces,
        ] = GetClassProperties::readClassDefinition($tokens);

        // It means that, there is no class/trait definition found in the file.
        if (! $class) {
            return;
        }

        event('laravel_microscope.checking_file', [$absFilePath]);

        $isFixed = self::checkAtSignStrings($tokens, $absFilePath);

        $isFixed && $tokens = token_get_all(file_get_contents($absFilePath));

        return self::checkNotImportedClasses($tokens, $absFilePath, $imports);
    }

    public static function isAbsent($class)
    {
        return ! \class_exists($class) && ! \interface_exists($class) && ! \trait_exists($class);
    }

    public static function checkAtSignStrings($tokens, $absFilePath, $onlyAbsClassPath = false)
    {
        $printer = app(ErrorPrinter::class);
        $fix = false;

        foreach ($tokens as $token) {
            // If it is a string containing a single '@'
            if ($token[0] != T_CONSTANT_ENCAPSED_STRING || \substr_count($token[1], '@') != 1) {
                continue;
            }

            $trimmed = \trim($token[1], '\'\"');

            if ($onlyAbsClassPath && $trimmed[0] !== '\\') {
                continue;
            }

            [$class, $method] = \explode('@', $trimmed);

            if (\substr_count($class, '\\') <= 0) {
                continue;
            }

            if (Str::contains($trimmed, ['-', '/', '[', '*', '+', '.', '(', '$', '^'])) {
                continue;
            }

            $class = str_replace('\\\\', '\\', $class);

            if (! \class_exists($class)) {
                $isInUserSpace = self::isInUserSpace($class);

                $result = [false];
                if ($isInUserSpace) {
                    $result = Analyzers\Fixer::fixReference($absFilePath, $class, $token[2]);
                }

                if ($result[0]) {
                    $fix = true;
                    $printer->printFixation($absFilePath, $class, $token[2], $result[1]);
                } else {
                    $printer->wrongUsedClassError($absFilePath, $token[1], $token[2]);
                }
            } elseif (! \method_exists($class, $method)) {
                $printer->wrongMethodError($absFilePath, $trimmed, $token[2]);
            }
        }

        return $fix;
    }

    private static function fixClassReference($absFilePath, $class, $line, $namespace)
    {
        $baseClassName = Str::replaceFirst($namespace.'\\', '', $class);

        // imports the correct namespace
        [$wasCorrected, $corrections] = Analyzers\Fixer::fixReference($absFilePath, $baseClassName, $line);

        if ($wasCorrected) {
            return [$wasCorrected, $corrections];
        }

        return Analyzers\Fixer::fixReference($absFilePath, $class, $line);
    }

    private static function checkNotImportedClasses($tokens, $absFilePath, $imports)
    {
        [$classReferences, $hostNamespace, $unusedRefs] = self::findClassRefs($tokens, $absFilePath, $imports);

        $printer = app(ErrorPrinter::class);

        foreach ($unusedRefs as $class) {
            CheckClassReferences::$refCount++;
            if (! self::isAbsent($class[0])) {
                $printer->extraImport($absFilePath, $class[0], $class[1]);
            } else {
                //$isCorrected = self::tryToFix($classImport, $absFilePath, $line, $as, $printer);
                //if (! $isCorrected) {
                $printer->wrongImport($absFilePath, $class[0], $class[1]);
                //} else {
                //    $fixed = true;
                //}
            }
        }

        loopStart:
        foreach ($classReferences as $y => $classReference) {
            CheckClassReferences::$refCount++;
            $class = $classReference['class'];
            $line = $classReference['line'];

            if (! self::isAbsent($class) || \function_exists($class)) {
                continue;
            }

            require_once $absFilePath;

            if (! self::isAbsent($class) || \function_exists($class)) {
                continue;
            }
            // renames the variable
            $wrongClassRef = $class;
            unset($class);
            if (! self::isInUserSpace($wrongClassRef)) {
                $printer->doesNotExist($wrongClassRef, $absFilePath, $line, 'wrongReference', 'Inline class Ref does not exist:');
                continue;
            }

            [$isFixed, $corrections] = self::fixClassReference($absFilePath, $wrongClassRef, $line, $hostNamespace);

            // print
            $method = $isFixed ? 'printFixation' : 'wrongImportPossibleFixes';
            $printer->$method($absFilePath, $wrongClassRef, $line, $corrections);

            if ($isFixed) {
                $tokens = token_get_all(file_get_contents($absFilePath));
                [$classReferences, $hostNamespace] = self::findClassRefs($tokens, $absFilePath, $imports);
                unset($classReferences[$y]);
                goto loopStart;
            }
        }

        return $tokens;
    }

    public static function findClassRefs($tokens, $absFilePath, $imports)
    {
        try {
            //[$classReferences, $hostNamespace] = ParseUseStatement::findClassReferences($tokens);
            [$classes, $namespace] = ClassReferenceFinder::process($tokens);

            $docblockRefs = ClassReferenceFinder::readRefsInDocblocks($tokens);
            $unusedRefs = ParseUseStatement::getUnusedImports($classes, $imports, $docblockRefs);
            [$classReferences, $hostNamespace,] = ClassRefExpander::expendReferences($classes, $imports, $namespace);

            return [$classReferences, $hostNamespace, $unusedRefs, $docblockRefs];
        } catch (ErrorException $e) {
            self::requestIssue($absFilePath);

            return [[], '', []];
        }
    }

    private static function requestIssue(string $path)
    {
        dump('(O_o)   Well, It seems we had some problem parsing the contents of:   (o_O)');
        dump('Submit an issue on github: https://github.com/imanghafoori1/microscope');
        dump('Send us the contents of: '.$path);
    }

    public static function isInUserSpace($class): bool
    {
        $isInUserSpace = false;
        $class = ltrim($class, '\\');
        foreach (ComposerJson::readAutoload() as $autoload) {
            if (Str::startsWith($class, \array_keys($autoload))) {
                $isInUserSpace = true;
            }
        }

        return $isInUserSpace;
    }
}
