<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Illuminate\Support\Str;

class NamespaceCorrector
{
    public static function getNamespaceFromFullClass($class)
    {
        $segments = explode('\\', $class);
        array_pop($segments); // removes the last part

        return trim(implode('\\', $segments), '\\');
    }

    public static function haveSameNamespace($class1, $class2)
    {
        return self::getNamespaceFromFullClass($class1) == self::getNamespaceFromFullClass($class2);
    }

    public static function fix($classFilePath, $incorrectNamespace, $correctNamespace)
    {
        // decides to add namespace (in case there is no namespace) or edit the existing one.
        [$oldLine, $newline] = self::getNewLine($incorrectNamespace, $correctNamespace);

        $oldLine = \ltrim($oldLine, '\\');
        FileManipulator::replaceFirst($classFilePath, $oldLine, $newline);
    }

    public static function calculateCorrectNamespace($relativeClassPath, $composerPath, $rootNamespace)
    {
        // removes the filename.php from the end of the string
        $classPath = \explode(DIRECTORY_SEPARATOR, $relativeClassPath);
        // removes the filename
        array_pop($classPath);
        // ensure back slashes.
        $classPath = \implode('\\', $classPath);

        $composerPath = \str_replace('/', '\\', $composerPath);

        // replace composer base_path with composer namespace
        /**
         *  "psr-4": {
         *      "App\\": "app/"
         *  }.
         */
        return Str::replaceFirst(\trim($composerPath, '\\'), \trim($rootNamespace, '\\/'), $classPath);
    }

    private static function getNewLine($incorrectNamespace, $correctNamespace)
    {
        if ($incorrectNamespace) {
            return [$incorrectNamespace, $correctNamespace];
        }

        // In case there is no namespace specified in the file:
        return ['<?php', '<?php'.PHP_EOL.PHP_EOL.'namespace '.$correctNamespace.';'.PHP_EOL];
    }

    public static function getRelativePathFromNamespace($namespace)
    {
        $autoload = ComposerJson::readAutoload();
        uksort($autoload, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });
        $namespaces = array_keys($autoload);
        $paths = array_values($autoload);

        return \str_replace(['\\', '/'], DIRECTORY_SEPARATOR, \str_replace($namespaces, $paths, $namespace));
    }

    public static function getNamespaceFromRelativePath($relPath)
    {
        // Remove .php from class path
        $relPath = str_replace([base_path(), '.php'], '', $relPath);

        $autoload = ComposerJson::readAutoload();
        uksort($autoload, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        $namespaces = array_keys($autoload);
        $paths = array_values($autoload);

        $relPath = \str_replace('\\', '/', $relPath);

        return trim(\str_replace('/', '\\', \str_replace($paths, $namespaces, $relPath)), '\\');
    }
}
