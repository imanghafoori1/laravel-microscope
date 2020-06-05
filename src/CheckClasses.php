<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Str;
use Illuminate\Support\Composer;
use Imanghafoori\LaravelMicroscope\Analyzers\ReplaceLine;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Analyzers\GetClassProperties;
use Imanghafoori\LaravelMicroscope\Analyzers\ParseUseStatement;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckClasses
{
    public static function check($tokens, $absFilePath)
    {
        try {
            self::checkImports($tokens, $absFilePath);
        } catch (\ErrorException $e) {
            // In case a file is moved or deleted...
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

    private static function checkImports($tokens, $absFilePath)
    {
        // If file is empty or does not begin with <?php
        if (($tokens[0][0] ?? null) !== T_OPEN_TAG) {
            return ;
        }

        [
            $currentNamespace,
            $class,
            $type,
            $parent,
            $interfaces
        ] = GetClassProperties::readClassDefinition($tokens);

        // It means that, there is no class/trait definition found in the file.
        if (! $class) {
            return ;
        }

        event('laravel_microscope.checking_file', [$absFilePath]);
        // @todo better to do it an event listener.

        self::checkAtSignStrings($tokens, $absFilePath);

        self::checkNotImportedClasses($tokens, $absFilePath);

        self::checkImportedClasses($currentNamespace, $class, $absFilePath);
    }

    /**
     * Calculate the namespace\className from absolute file path.
     *
     * @param  string  $filePath
     * @param  string  $basePath
     * @param  string  $path
     * @param  string  $rootNamespace
     *
     * @return string
     */
    protected static function calculateClassFromFile($filePath, $basePath, $path, $rootNamespace)
    {
        $class = trim(Str::replaceFirst($basePath, '', $filePath), DIRECTORY_SEPARATOR);

        // remove .php from class path
        $withoutDotPhp = Str::replaceLast('.php', '', $class);
        // ensure backslash on windows
        $allBackSlash = str_replace(DIRECTORY_SEPARATOR, '\\', $withoutDotPhp);

        // replaces the base folder name with corresponding namespace
        return str_replace(rtrim($path, '/').'\\', $rootNamespace, $allBackSlash);
    }

    private static function checkImportedClassesExist($imports, $absFilePath)
    {
        foreach ($imports as $i => $import) {
            if (self::isAbsent($import[0])) {
                $isInUserSpace = Str::startsWith($import[0], array_keys(ComposerJson::readAutoload()));
                if ($isInUserSpace && ReplaceLine::fixReference($absFilePath, $import[0], $import[1])) {
                    self::printFixation($absFilePath, $import[0], $import[1]);
                } else {
                    app(ErrorPrinter::class)->wrongImport($absFilePath, $import[0], $import[1]);
                }
            }
        }
    }

    public static function isAbsent($class)
    {
        return ! class_exists($class) && ! interface_exists($class) && ! trait_exists($class);
    }

    protected static function fullNamespace($currentNamespace, $class)
    {
        if ($currentNamespace) {
            $namespacedClassName = $currentNamespace.'\\'.$class;
        } else {
            $namespacedClassName = $class;
        }

        return $namespacedClassName;
    }

    public static function checkAtSignStrings($tokens, $absFilePath, $onlyAbsClassPath = false)
    {
        foreach ($tokens as $token) {
            // if it is a string containing a single '@'
            if ($token[0] != T_CONSTANT_ENCAPSED_STRING || substr_count($token[1], '@') != 1) {
                continue;
            }

            $trimmed = trim($token[1], '\'\"');

            if ($onlyAbsClassPath && $trimmed[0] !== '\\') {
                continue;
            }

            [$class, $method] = explode('@', $trimmed);

            if (substr_count($class, '\\') <= 0) {
                continue;
            }


            if (Str::contains($trimmed, ['-', '/', '[', '*', '+', '.', '(', '$', '^'])) {
                continue;
            }

            if (! class_exists($class)) {
                $isInUserSpace = Str::startsWith($class, array_keys(ComposerJson::readAutoload()));
                if ($isInUserSpace && ReplaceLine::fixReference($absFilePath, $class, $token[2])) {
                    self::printFixation($absFilePath, $class, $token[2]);
                } else {
                    app(ErrorPrinter::class)->wrongUsedClassError($absFilePath, $token[1], $token[2]);
                }
            } elseif (! method_exists($class, $method)) {
                app(ErrorPrinter::class)->wrongMethodError($absFilePath, $trimmed, $token[2]);
            }
        }
    }

    private static function checkImportedClasses($currentNamespace, $class, $absPath)
    {
        $namespacedClassName = self::fullNamespace($currentNamespace, $class);

        $imports = ParseUseStatement::getUseStatementsByPath($namespacedClassName, $absPath);

        self::checkImportedClassesExist($imports, $absPath);
    }

    private static function checkNotImportedClasses($tokens, $absFilePath)
    {
        $nonImportedClasses = ParseUseStatement::findClassReferences($tokens, $absFilePath);

        foreach ($nonImportedClasses as $nonImportedClass) {
            $cls = trim($nonImportedClass['class'], '\\');
            if (self::isAbsent($cls) && ! function_exists($cls)) {
                $isInUserSpace = Str::startsWith($cls, array_keys(ComposerJson::readAutoload()));
                $line = $nonImportedClass['line'];
                if ($isInUserSpace && ReplaceLine::fixReference($absFilePath, $cls, $line)) {
                    self::printFixation($absFilePath, $cls, $line);
                } else {
                    app(ErrorPrinter::class)->wrongUsedClassError($absFilePath, $cls, $line);
                }
            }
        }
    }

    private static function printFixation($absPath, $class, $line)
    {
        app(ErrorPrinter::class)->simplePendError($absPath, $line, $class, 'ns_replacement', 'Namespace auto-corrected');
    }
}
