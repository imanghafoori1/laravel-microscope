<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\TokenAnalyzer\GetClassProperties;

class CheckNamespaces
{
    public static $checkedNamespaces = 0;

    public static $cacheData = [];

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
     * @param $path
     * @return void
     */
    public static function all($detailed, $path)
    {
        $autoload = ComposerJson::readAutoloadInPath($path);
        self::$cacheData = cache()->get('microscope_psr4:');
        //self::$cacheData = [];
        $scanned = [];
        foreach ($autoload as $namespace => $psr4Path) {

            // to avoid duplicate scanning
            foreach ($scanned as $s) {
                if (strlen($psr4Path) > strlen($s) && Str::startsWith($psr4Path, $s)) {
                    continue 2;
                }
            }

            $scanned[] = $psr4Path;

            CheckNamespaces::within($namespace, $psr4Path, $detailed);
        }

        cache()->put('microscope_psr4:', self::$cacheData, now()->addDays(3));
    }

    public static function within($namespace, $composerPath, $detailed)
    {
        $paths = FilePath::getAllPhpFiles($composerPath);

        foreach ($paths as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();

            // Exclude blade files
            if (Str::endsWith($absFilePath, ['.blade.php'])) {
                continue;
            }

            $relativePath = FilePath::getRelativePath($absFilePath);

            self::$checkedNamespaces++;

            if ((self::$cacheData[self::getKey($relativePath, $namespace)] ?? 0) === filemtime($absFilePath)) {
                continue;
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

            $correctNamespaces = self::getCorrectNamespaces($relativePath);

            if (in_array($currentNamespace, $correctNamespaces)) {
                self::remember($namespace, $relativePath, $absFilePath);
                continue;
            }
            $correctNamespace = self::findShortest($correctNamespaces);

            self::changeNamespace($absFilePath, $currentNamespace, $correctNamespace, $class);
        }
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
        $relPath = NamespaceCorrector::getRelativePathFromNamespace($currentNamespace);
        if (is_dir(base_path($relPath.DIRECTORY_SEPARATOR.$class))) {
            self::$changedNamespaces[$_currentClass.';'] = $_correctClass.';';
            self::$changedNamespaces[$_currentClass.'('] = $_correctClass.'(';
            self::$changedNamespaces[$_currentClass.'::'] = $_correctClass.'::';
            self::$changedNamespaces[$_currentClass.' as'] = $_correctClass.' as';
        } else {
            self::$changedNamespaces[$_currentClass] = $_correctClass;
        }
    }

    private static function getCorrectNamespaces($relativePath)
    {
        $namespaces = ComposerJson::readAutoload();
        $correctNamespaces = [];
        foreach ($namespaces as $namespacePrefix => $path) {
            if (substr(str_replace('\\', '/', $relativePath), 0, strlen($path)) === $path) {
                $correctNamespaces[] = NamespaceCorrector::calculateCorrectNamespace($relativePath, $path, $namespacePrefix);
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

    private static function getKey($relativePath, $namespace)
    {
        return 'check:psr4-'.$relativePath.$namespace;
    }

    private static function remember($namespace, $relativePath, $absFilePath)
    {
        self::$cacheData[self::getKey($relativePath, $namespace)] = filemtime($absFilePath);
    }
}
