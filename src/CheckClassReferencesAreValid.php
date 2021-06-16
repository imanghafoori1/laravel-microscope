<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Analyzers\FileManipulator;
use Imanghafoori\LaravelMicroscope\Analyzers\GetClassProperties;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;
use Imanghafoori\LaravelMicroscope\Analyzers\ParseUseStatement;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

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

        self::checkAtSignStrings($tokens, $absFilePath);

        self::checkNotImportedClasses($tokens, $absFilePath);

        self::checkImportedClasses($currentNamespace, $class, $absFilePath);
    }

    private static function checkImportedClassesExist($imports, $absFilePath)
    {
        $printer = app(ErrorPrinter::class);

        foreach ($imports as $i => $import) {
            [$class, $line] = $import;

            if (! self::isAbsent($class)) {
                continue;
            }

            if (\is_dir(base_path(NamespaceCorrector::getRelativePathFromNamespace($class)))) {
                continue;
            }

            $isInUserSpace = Str::startsWith($class, array_keys(ComposerJson::readAutoload()));

            [$isCorrected, $corrects] = FileManipulator::fixReference($absFilePath, $class, $line, '', true);

            if ($isInUserSpace && $isCorrected) {
                $printer->printFixation($absFilePath, $class, $line, $corrects);
            } else {
                $printer->wrongImport($absFilePath, $class, $line);
            }
        }
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

            if (! \class_exists($class)) {
                $isInUserSpace = Str::startsWith($class, \array_keys(ComposerJson::readAutoload()));
                $result = FileManipulator::fixReference($absFilePath, $class, $token[2]);
                if ($isInUserSpace && $result[0]) {
                    $printer->printFixation($absFilePath, $class, $token[2], $result[1]);
                } else {
                    $printer->wrongUsedClassError($absFilePath, $token[1], $token[2]);
                }
            } elseif (! \method_exists($class, $method)) {
                $printer->wrongMethodError($absFilePath, $trimmed, $token[2]);
            }
        }
    }

    private static function checkImportedClasses($currentNamespace, $class, $absPath)
    {
        $namespacedClassName = self::fullNamespace($currentNamespace, $class);

        $imports = ParseUseStatement::getUseStatementsByPath($namespacedClassName, $absPath);

        self::checkImportedClassesExist($imports, $absPath);
    }

    private static function fix($absFilePath, $class, $line, $namespace)
    {
        $baseClassName = \str_replace($namespace.'\\', '', $class);

        $result = FileManipulator::fixReference($absFilePath, $baseClassName, $line, '\\');

        if ($result[0]) {
            return $result;
        }

        return $result = FileManipulator::fixReference($absFilePath, $class, $line);
    }

    private static function checkNotImportedClasses($tokens, $absFilePath)
    {
        [$nonImportedClasses, $namespace] = ParseUseStatement::findClassReferences($tokens, $absFilePath);

        $printer = app(ErrorPrinter::class);

        loopStart:
        foreach ($nonImportedClasses as $nonImportedClass) {
            $class = $nonImportedClass['class'];
            $line = $nonImportedClass['line'];

            if (! self::isAbsent($class) || \function_exists($class)) {
                continue;
            }

            if (! ComposerJson::isInAppSpace($class)) {
                $printer->doesNotExist($class, $absFilePath, $line, 'wrongReference', 'Class does not exist:');
                continue;
            }

            [$isFixed, $corrections] = self::fix($absFilePath, $class, $line, $namespace);

            $method = $isFixed ? 'printFixation' : 'wrongImportPossibleFixes';
            $printer->$method($absFilePath, $class, $line, $corrections);
            if ($isFixed) {
                $tokens = token_get_all(file_get_contents($absFilePath));
                ([$nonImportedClasses, $namespace] = ParseUseStatement::findClassReferences($tokens, $absFilePath));
                goto loopStart;
            }
        }
    }
}
