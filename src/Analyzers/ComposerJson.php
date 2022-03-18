<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Imanghafoori\TokenAnalyzer\Str;

class ComposerJson
{
    private static $result = [];

    /**
     * Used for testing purposes.
     */
    public static $composerPath = null;

    private static function readKey($key, $composerPath = '')
    {
        $composer = self::readComposerFileData(app()->basePath($composerPath));

        $value = (array) data_get($composer, $key, []);

        if (\in_array($key, ['autoload.psr-4', 'autoload-dev.psr-4'])) {
            $value = self::normalizePaths($value, $composerPath);
        }

        return $value;
    }

    public static function isInUserSpace($class)
    {
        return Str::startsWith(ltrim($class, '\\'), \array_keys(ComposerJson::readAutoload()));
    }

    public static function readAutoload()
    {
        $result = [];

        foreach (self::collectLocalRepos() as $path) {
            // We avoid autoload-dev for repositories.
            $result = $result + self::readKey('autoload.psr-4', $path) + self::readKey('autoload-dev.psr-4', $path);
        }

        // add the root composer.json
        $root = self::readKey('autoload.psr-4') + self::readKey('autoload-dev.psr-4');

        return self::removedIgnored($result + $root, config('microscope.ignored_namespaces', []));
    }

    private static function normalizePaths($value, $path)
    {
        $path && $path = Str::finish($path, '/');
        foreach ($value as $namespace => $_path) {
            if (is_array($_path)) {
                foreach ($_path as $i => $p) {
                    $value[$namespace][$i] = str_replace('//', '/', $path.Str::finish($p, '/'));
                }
            } else {
                $value[$namespace] = str_replace('//', '/', $path.Str::finish($_path, '/'));
            }
        }

        return $value;
    }

    private static function removedIgnored($mapping, $ignored = [])
    {
        $result = [];

        foreach ($mapping as $namespace => $path) {
            if (! in_array($namespace, $ignored)) {
                $result[$namespace] = $path;
            }
        }

        return $result;
    }

    /**
     * @param $composerPath
     * @return array
     */
    private static function readComposerFileData($composerPath)
    {
        $fullPath = self::$composerPath ?: $composerPath;

        $fullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);

        // ensure it does not end with slash
        $fullPath = rtrim($fullPath, DIRECTORY_SEPARATOR);

        if (! isset(self::$result[$fullPath])) {
            self::$result[$fullPath] = \json_decode(\file_get_contents($fullPath.'/composer.json'), true);
        }

        return self::$result[$fullPath];
    }

    public static function collectLocalRepos()
    {
        $composers = [];

        foreach (self::readKey('repositories') as $repo) {
            if (! isset($repo['type']) || $repo['type'] !== 'path') {
                continue;
            }

            // here we exclude local packages outside the root folder.
            if (Str::contains($repo['url'], '../')) {
                continue;
            }
            $dirPath = \trim(\trim($repo['url'], '.'), '/\\');
            // sometimes php can not detect relative paths, so we use the absolute path here.
            if (file_exists(base_path($dirPath.DIRECTORY_SEPARATOR.'composer.json'))) {
                $composers[] = $dirPath;
            }
        }

        return $composers;
    }
}
