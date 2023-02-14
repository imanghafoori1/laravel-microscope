<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

class NamespaceCalculator
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

    public static function getRelativePathFromNamespace($namespace, $autoload = null)
    {
        [$namespaces, $paths] = self::getSortedAutoload($autoload);
        [$namespaces, $paths] = self::flatten($paths, $namespaces);

        return \str_replace(['\\', '/'], DIRECTORY_SEPARATOR, \str_replace($namespaces, $paths, $namespace));
    }

    public static function getNamespacedClassFromPath($absPath, $basePath, $autoload)
    {
        [$namespaces, $paths] = self::getSortedAutoload($autoload);

        // Remove .php from class path
        $relPath = str_replace([$basePath, '.php'], '', $absPath);
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
}
