<?php

namespace Imanghafoori\LaravelMicroscope;

use ImanGhafoori\ComposerJson\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\TokenAnalyzer\Str;

use function str_replace;
use function trim;

class ClassListProvider
{
    /**
     * @var array<string, array>
     */
    public static $allNamespaces = [];

    public static function get()
    {
        if (self::$allNamespaces) {
            return self::$allNamespaces;
        }

        foreach (self::getCandidateSearchPaths() as $baseComposerPath => $psr4) {
            foreach ($psr4 as $folder => $psr4Mappings) {
                foreach ((array) $psr4Mappings as $namespace => $_psr4Paths) {
                    foreach ((array) $_psr4Paths as $psr4Path) {
                        self::calculate($psr4Path, $baseComposerPath, $namespace);
                    }
                }
            }
        }

        return self::$allNamespaces;
    }

    private static function calculate($psr4Path, $baseComposerPath, $namespace): void
    {
        foreach (FilePath::getAllPhpFiles($psr4Path, $baseComposerPath) as $classFilePath) {
            $fileName = $classFilePath->getFilename();
            if (substr_count($fileName, '.') > 1) {
                continue;
            }

            $relativePath = str_replace($baseComposerPath ?: base_path(), '', $classFilePath->getRealPath());

            [$classBaseName, $fullClassPath] = self::derive($psr4Path, $relativePath, $namespace, $fileName);
            self::$allNamespaces[$classBaseName][] = $fullClassPath;
        }
    }

    private static function getCandidateSearchPaths()
    {
        $sp = DIRECTORY_SEPARATOR;
        $path1 = base_path();
        $path2 = base_path('vendor'.$sp.'laravel'.$sp.'framework');

        return [
            $path1 => Analyzers\ComposerJson::make()->readAutoload(),
            $path2 => ComposerJson::make($path2)->readAutoload(),
        ];
    }

    public static function derive($psr4Path, $relativePath, $namespace, $fileName): array
    {
        $composerPath = str_replace('/', '\\', $psr4Path);
        $relativePath = str_replace('/', '\\', $relativePath);

        /**
         * // replace composer base_path with composer namespace
         *  "psr-4": {
         *      "App\\": "app/"
         *  }.
         */
        // calculate namespace
        $ns = Str::replaceFirst(trim($composerPath, '\\'), trim($namespace, '\\/'), $relativePath);
        $t = str_replace('.php', '', [$ns, $fileName]);
        $t = str_replace('/', '\\', $t); // for linux environments.

        $classBaseName = $t[1];
        $fullClassPath = $t[0];

        return [$classBaseName, trim($fullClassPath, '\\')];
    }
}
