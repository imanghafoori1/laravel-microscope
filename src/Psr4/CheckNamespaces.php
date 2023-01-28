<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\TokenAnalyzer\GetClassProperties;

class CheckNamespaces
{
    public static $checkedNamespaces = 0;

    public static $checkedNamespacesStats = [];

    public static $changedNamespaces = [];

    public static function reset()
    {
        self::$changedNamespaces = [];
        self::$checkedNamespaces = 0;
    }

    /**
     * Checks all the psr-4 loaded classes to have correct namespace.
     *
     * @param  $detailed
     * @return array
     */
    public static function all($detailed)
    {
        $scanned = [];
        $classes = [];
        foreach (ComposerJson::readAutoload() as $autoload) {
            foreach ($autoload as $namespace => $psr4Path) {
                // to avoid duplicate scanning
                foreach ($scanned as $s) {
                    if (strlen($psr4Path) > strlen($s) && Str::startsWith($psr4Path, $s)) {
                        continue 2;
                    }
                }

                $scanned[] = $psr4Path;

                $classes = array_merge($classes, CheckNamespaces::getClassesWithin($namespace, $psr4Path, $detailed));
            }
        }

        return $classes;
    }

    public static function getClassesWithin($namespace, $composerPath, $detailed)
    {
        $paths = FilePath::getAllPhpFiles($composerPath);

        $results = [];
        foreach ($paths as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();

            // Exclude blade files
            if (substr_count($absFilePath, '.') === 2) {
                continue;
            }

            self::$checkedNamespaces++;

            if (isset(self::$checkedNamespacesStats[$namespace])) {
                self::$checkedNamespacesStats[$namespace]++;
            } else {
                self::$checkedNamespacesStats[$namespace] = 1;
            }

            [
                $currentNamespace,
                $class,
                $type,
                $parent,
            ] = GetClassProperties::fromFilePath($absFilePath, config('microscope.class_search_buffer', 2500));

            // Skip if there is no class/trait/interface definition found.
            // For example a route file or a config file.
            if (! $class || $parent === 'Migration') {
                continue;
            }

            $detailed && event('microscope.checking', [$classFilePath->getRelativePathname()]);

            $results[] = [
                'currentNamespace' => $currentNamespace,
                'absFilePath' => $absFilePath,
                'class' => $class,
            ];
        }

        return $results;
    }

    public static function changeNamespace($absPath, $from, $to, $class)
    {
        $fix = event('laravel_microscope.namespace_fixing', get_defined_vars(), true);

        if ($fix !== false) {
            self::changedNamespaces($class, $from, $to);
            NamespaceCorrector::fix($absPath, $from, $to);
        }

        event('laravel_microscope.namespace_fixed', get_defined_vars());
    }

    private static function changedNamespaces($class, $currentNamespace, $correctNamespace)
    {
        if (! $currentNamespace) {
            return null;
        }

        $_currentClass = $currentNamespace.'\\'.$class;
        $_correctClass = $correctNamespace.'\\'.$class;
        self::$changedNamespaces[$_currentClass.';'] = $_correctClass.';';
        self::$changedNamespaces[$_currentClass.'('] = $_correctClass.'(';
        self::$changedNamespaces[$_currentClass.'::'] = $_correctClass.'::';
        self::$changedNamespaces[$_currentClass.' as'] = $_correctClass.' as';
    }

    private static function getCorrectNamespaces($relativePath)
    {
        $correctNamespaces = [];
        foreach (ComposerJson::readAutoload() as $autoload) {
            foreach ($autoload as $namespacePrefix => $path) {
                if (substr(str_replace('\\', '/', $relativePath), 0, strlen($path)) === $path) {
                    $correctNamespaces[] = NamespaceCorrector::calculateCorrectNamespace($relativePath, $path, $namespacePrefix);
                }
            }
        }

        return $correctNamespaces;
    }

    private static function findShortest($correctNamespaces)
    {
        // finds the shortest namespace
        return array_reduce($correctNamespaces, function ($a, $b) {
            if ($a === null) {
                return $b;
            }

            return strlen($a) < strlen($b) ? $a : $b;
        });
    }

    public static function checkNamespace($currentNamespace, $absFilePath, $class)
    {
        $relativePath = FilePath::getRelativePath($absFilePath);
        $correctNamespaces = self::getCorrectNamespaces($relativePath);

        if (! in_array($currentNamespace, $correctNamespaces)) {
            $correctNamespace = self::findShortest($correctNamespaces);

            return [
                'absPath' => $absFilePath,
                'from' => $currentNamespace,
                'to' => $correctNamespace,
                'class' => $class,
                'type' => 'namespace',
            ];
        } elseif (($class.'.php') !== basename($absFilePath)) {
            return [
                'relativePath' => $relativePath,
                'fileName' => basename($absFilePath),
                'class' => $class,
                'type' => 'filename',
            ];
        }
    }
}
