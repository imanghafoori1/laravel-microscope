<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Imanghafoori\TokenAnalyzer\Str;
use JetBrains\PhpStorm\Pure;

class ComposerJson
{
    private static $result = [];

    /**
     * Used for testing purposes.
     */
    public static $composerPath = null;

    public static function readAutoload()
    {
        $result = [];

        foreach (self::collectLocalRepos() as $relativePath) {
            // We avoid autoload-dev for repositories.
            $result[$relativePath] = self::readKey('autoload.psr-4', $relativePath) + self::readKey('autoload-dev.psr-4', $relativePath);
        }

        // add the root composer.json
        $result['/'] = self::readKey('autoload.psr-4') + self::readKey('autoload-dev.psr-4');

        return self::removedIgnored($result, config('microscope.ignored_namespaces', []));
    }

    public static function collectLocalRepos()
    {
        $composers = [];

        foreach (self::readKey('repositories') as $repo) {
            if (! isset($repo['type']) || $repo['type'] !== 'path') {
                continue;
            }

            // here we exclude local packages outside the root folder.
            if (Str::startsWith($repo['url'], ['../', './../', '/../'])) {
                continue;
            }
            $dirPath = \trim(\trim($repo['url'], '.'), '/\\');
            $path = (self::$composerPath ?: base_path()).DIRECTORY_SEPARATOR.$dirPath.DIRECTORY_SEPARATOR.'composer.json';
            // sometimes php can not detect relative paths, so we use the absolute path here.
            if (file_exists($path)) {
                $composers[] = $dirPath;
            }
        }

        return $composers;
    }

    #[Pure]
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

        foreach ($mapping as $i => $map) {
            foreach ($map as $namespace => $path) {
                if (! in_array($namespace, $ignored)) {
                    $result[$i][$namespace] = $path;
                }
            }
        }

        return $result;
    }

    private static function readKey($key, $composerPath = '')
    {
        if (self::$composerPath) {
            $absPath = self::$composerPath.DIRECTORY_SEPARATOR.$composerPath;
        } else {
            $absPath = app()->basePath($composerPath);
        }

        $composer = self::readComposerFileData($absPath);

        $value = (array) data_get($composer, $key, []);

        if (\in_array($key, ['autoload.psr-4', 'autoload-dev.psr-4'])) {
            $value = self::normalizePaths($value, $composerPath);
        }

        return $value;
    }

    /**
     * @param  string  $absPath
     *
     * @return array
     */
    private static function readComposerFileData($absPath)
    {
        $absPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $absPath);

        // ensure it does not end with slash
        $absPath = rtrim($absPath, DIRECTORY_SEPARATOR);

        if (! isset(self::$result[$absPath])) {
            self::$result[$absPath] = \json_decode(\file_get_contents($absPath.DIRECTORY_SEPARATOR.'composer.json'), true);
        }

        return self::$result[$absPath];
    }
}
