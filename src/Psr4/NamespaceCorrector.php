<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

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
        return self::getNamespaceFromFullClass($class1) === self::getNamespaceFromFullClass($class2);
    }

    public static function calculateCorrectNamespace($relativeClassPath, $composerPath, $rootNamespace)
    {
        $classPath = \explode(DIRECTORY_SEPARATOR, $relativeClassPath);
        // Removes the filename
        array_pop($classPath);

        $classPath = \implode('\\', $classPath);

        // Ensure back slashes in All Operating Systems.
        $composerPath = \str_replace('/', '\\', $composerPath);

        // replace composer base_path with composer namespace
        /**
         *  "psr-4": {
         *      "App\\": "app/"
         *  }.
         */
        return self::replaceFirst(\trim($composerPath, '\\'), \trim($rootNamespace, '\\/'), $classPath);
    }

    public static function getRelativePathFromNamespace($namespace, $autoload = null)
    {
        [$namespaces, $paths] = self::getSortedAutoload($autoload);
        [$namespaces, $paths] = self::flatten($paths, $namespaces);

        return \str_replace(['\\', '/'], DIRECTORY_SEPARATOR, \str_replace($namespaces, $paths, $namespace));
    }

    public static function getNamespacedClassFromPath($path, $autoload = null)
    {
        [$namespaces, $paths] = self::getSortedAutoload($autoload);

        // Remove .php from class path
        $relPath = str_replace([base_path(), '.php'], '', $path);
        $relPath = \str_replace('\\', '/', $relPath);

        [$_namespaces, $_paths] = self::flatten($paths, $namespaces);

        return trim(\str_replace('/', '\\', \str_replace($_paths, $_namespaces, $relPath)), '\\');
    }

    private static function getSortedAutoload($autoloads)
    {
        $namespaces = [];
        $paths = [];

        foreach ($autoloads as $autoload) {
            uasort($autoload, function ($path, $path2) {
                return strlen($path2) <=> strlen($path);
            });

            $namespaces = array_merge($namespaces, array_keys($autoload));
            $paths = array_merge($paths, array_values($autoload));
        }

        return [$namespaces, $paths];
    }

    private static function flatten($paths, $namespaces)
    {
        $_namespaces = [];
        $_paths = [];
        $counter = 0;
        foreach ($paths as $k => $_p) {
            foreach ((array) $_p as $p) {
                $counter++;
                $_namespaces[$counter] = $namespaces[$k];
                $_paths[$counter] = $p;
            }
        }

        return [$_namespaces, $_paths];
    }

    public static function replaceFirst($search, $replace, $subject)
    {
        if ($search == '') {
            return $subject;
        }

        $position = strpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }
}
