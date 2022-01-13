<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\TokenAnalyzer\GetClassProperties;

class CheckNamespaces
{
    public static $checkedNamespaces = 0;

    public static $changedNamespaces = [];

    /**
     * Checks all the psr-4 loaded classes to have correct namespace.
     *
     * @param  $detailed
     * @return void
     */
    public static function all($detailed)
    {
        $autoload = ComposerJson::readAutoload();

        foreach ($autoload as $psr4Namespace => $psr4Path) {
            CheckNamespaces::within($psr4Path, $psr4Namespace, $detailed);
        }
    }

    public static function within($composerPath, $composerNamespace, $detailed)
    {
        $paths = FilePath::getAllPhpFiles($composerPath);

        foreach ($paths as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();

            // Exclude blade files
            if (Str::endsWith($absFilePath, ['.blade.php'])) {
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

            $relativePath = FilePath::getRelativePath($absFilePath);
            $correctNamespace = NamespaceCorrector::calculateCorrectNamespace($relativePath, $composerPath, $composerNamespace);
            self::$checkedNamespaces++;

            if ($currentNamespace === $correctNamespace) {
                continue;
            }

            // Sometimes, the class is loaded by other means of auto-loading
            if (! CheckClassReferencesAreValid::isAbsent($currentNamespace.'\\'.$class)) {
                continue;
            }

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
        unset($fix);

        event('laravel_microscope.namespace_fixed', get_defined_vars());
    }

    private static function changedNamespaces($class, $currentNamespace, $correctNamespace)
    {
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
}
