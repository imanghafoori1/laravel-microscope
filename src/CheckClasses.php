<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\GetClassProperties;
use Imanghafoori\LaravelMicroscope\Analyzers\ParseUseStatement;
use Imanghafoori\LaravelMicroscope\Contracts\FileCheckContract as FileCheckContractAlias;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckClasses
{
    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $files
     * @param  FileCheckContractAlias  $fileCheckContract
     *
     * @return void
     */
    public static function checkImports($files, FileCheckContractAlias $fileCheckContract)
    {
        foreach ($files as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();

            $tokens = token_get_all(file_get_contents($absFilePath));

            // If file is empty or does not begin with <?php
            if (($tokens[0][0] ?? null) !== T_OPEN_TAG) {
                continue;
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
                continue;
            }

            event('laravel_microscope.checking_file', [$absFilePath]);
            // @todo better to do it an event listener.
            $fileCheckContract->onFileTap($classFilePath);

            $tokens = token_get_all(file_get_contents($absFilePath));

            self::checkAtSignStrings($tokens, $absFilePath);

            $nonImportedClasses = ParseUseStatement::findClassReferences($tokens, $absFilePath);

            foreach ($nonImportedClasses as $nonImportedClass) {
                $v = trim($nonImportedClass['class'], '\\');
                if (self::isAbsent($v) && ! function_exists($v)) {
                    app(ErrorPrinter::class)->wrongUsedClassError($absFilePath, $nonImportedClass['class'], $nonImportedClass['line']);
                }
            }

            $namespacedClassName = self::fullNamespace($currentNamespace, $class);

            $imports = ParseUseStatement::getUseStatementsByPath($namespacedClassName, $absFilePath);
            self::checkImportedClassesExist($imports, $absFilePath);

            if ($currentNamespace) {
                if (is_subclass_of($currentNamespace.'\\'.$class, Model::class)) {
                    ModelRelations::checkModelRelations($tokens, $currentNamespace, $class, $absFilePath);
                }
            } else {
                // @todo show skipped file...
            }
        }
    }

    public static function hasOpeningTag($file)
    {
        $fp = fopen($file, 'r');

        if (feof($fp)) {
            return false;
        }

        $buffer = fread($fp, 20);
        fclose($fp);

        return Str::startsWith($buffer, '<?php');
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

    private static function checkImportedClassesExist($imports, $absPath)
    {
        foreach ($imports as $i => $import) {
            if (self::isAbsent($import[0])) {
                app(ErrorPrinter::class)->wrongImport($absPath, $import[0], $import[1]);
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

            if (! class_exists($class)) {
                app(ErrorPrinter::class)->wrongUsedClassError($absFilePath, $token[1], $token[2]);
            } else {
                if (! method_exists($class, $method)) {
                    app(ErrorPrinter::class)->wrongMethodError($absFilePath, $trimmed, $token[2]);
                }
            }
        }
    }
}
