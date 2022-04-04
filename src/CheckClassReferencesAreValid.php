<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Psr4\NamespaceCorrector;
use Imanghafoori\TokenAnalyzer\GetClassProperties;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class CheckClassReferencesAreValid
{
    public static function check($tokens, $absFilePath)
    {
        try {
            self::checkReferences($tokens, $absFilePath);
        } catch (\ErrorException $e) {
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

    private static function checkReferences($tokens, $absFilePath)
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
        // @todo better to do it an event listener.

        $isFixed = self::checkAtSignStrings($tokens, $absFilePath);

        $isFixed && $tokens = token_get_all(file_get_contents($absFilePath));

        $isFixed = self::checkImports($currentNamespace, $class, $absFilePath, $tokens);

        $isFixed && $tokens = token_get_all(file_get_contents($absFilePath));

        self::checkNotImportedClasses($tokens, $absFilePath);
    }

    private static function checkImportedClassesExist($imports, $absFilePath)
    {
        $printer = app(ErrorPrinter::class);
        $fixed = false;

        foreach ($imports as $as => $import) {
            [$classImport, $line] = $import;

            if (! self::isAbsent($classImport)) {
                continue;
            }

            // for half imported namespaces
            if (\is_dir(base_path(NamespaceCorrector::getRelativePathFromNamespace($classImport)))) {
                continue;
            }

            $isCorrected = self::tryToFix($classImport, $absFilePath, $line, $as, $printer);

            if (! $isCorrected) {
                $printer->wrongImport($absFilePath, $classImport, $line);
            } else {
                $fixed = true;
            }
        }

        return $fixed;
    }

    public static function isAbsent($class)
    {
        return ! \class_exists($class) && ! \interface_exists($class) && ! \trait_exists($class);
    }

    protected static function fullNamespace($currentNamespace, $class)
    {
        return $currentNamespace ? $currentNamespace.'\\'.$class : $class;
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
                $isInUserSpace = Str::startsWith($class, \array_keys(ComposerJson::readAutoload()));
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

    private static function checkImports($currentNamespace, $className, $absPath, $tokens)
    {
        $namespacedClassName = self::fullNamespace($currentNamespace, $className);

        $imports = ParseUseStatement::parseUseStatements($tokens, $namespacedClassName)[1];

        return self::checkImportedClassesExist($imports, $absPath);
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

    private static function checkNotImportedClasses($tokens, $absFilePath)
    {
        [$classReferences, $hostNamespace] = self::findClassRefs($tokens, $absFilePath);

        $printer = app(ErrorPrinter::class);

        loopStart:
        foreach ($classReferences as $classReference) {
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

            if (! ComposerJson::isInUserSpace($wrongClassRef)) {
                $printer->doesNotExist($wrongClassRef, $absFilePath, $line, 'wrongReference', 'Inline class Ref does not exist:');
                continue;
            }

            [$isFixed, $corrections] = self::fixClassReference($absFilePath, $wrongClassRef, $line, $hostNamespace);

            // print
            $method = $isFixed ? 'printFixation' : 'wrongImportPossibleFixes';
            $printer->$method($absFilePath, $wrongClassRef, $line, $corrections);

            if ($isFixed) {
                $tokens = token_get_all(file_get_contents($absFilePath));
                [$classReferences, $hostNamespace] = self::findClassRefs($tokens, $absFilePath);
                goto loopStart;
            }
        }
    }

    private static function isAliased($class, $as)
    {
        return class_basename($class) !== $as;
    }

    private static function tryToFix($classImport, $absFilePath, $line, $as, $printer)
    {
        $isInUserSpace = Str::startsWith($classImport, array_keys(ComposerJson::readAutoload()));
        if (! $isInUserSpace) {
            return false;
        }

        [$isCorrected, $corrects] = Analyzers\Fixer::fixImport($absFilePath, $classImport, $line, self::isAliased($classImport, $as));

        if ($isCorrected) {
            $printer->printFixation($absFilePath, $classImport, $line, $corrects);
        }

        return $isCorrected;
    }

    public static function findClassRefs($tokens, $absFilePath): array
    {
        try {
            [$classReferences, $hostNamespace] = ParseUseStatement::findClassReferences($tokens);

            return [$classReferences, $hostNamespace];
        } catch (\ErrorException $e) {
            self::requestIssue($absFilePath);

            return [[], ''];
        }
    }
}
