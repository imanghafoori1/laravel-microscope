<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Illuminate\Support\Str;

class ComposerJson
{
    private static $result = [];

    /**
     * Used for testing purposes.
     */
    public static $fakeComposerPath = null;

    public static function readKey($key, $composerPath = '')
    {
        $composer = self::readComposerFileData($composerPath);

        $value = (array) data_get($composer, $key, []);

        if (\in_array($key, ['autoload.psr-4', 'autoload-dev.psr-4'])) {
            $value = self::normalizePaths($value, $composerPath);
        }

        return $value;
    }

    public static function isInUserSpace($class)
    {
        return Str::startsWith($class, \array_keys(ComposerJson::readAutoload()));
    }

    public static function readAutoload()
    {
        $composers = [];
        foreach (self::readKey('repositories') as $repo) {
            if ($repo['type'] == 'path') {
                // here we exclude local packages outside of the root folder.
                ! Str::contains($repo['url'], '../') && $composers[] = \trim(\trim($repo['url'], '.'), '/').DIRECTORY_SEPARATOR.'';
            }
        }

        $result = [];
        foreach ($composers as $path) {
            // We avoid autoload-dev for repositories.
            $result = $result + self::readKey('autoload.psr-4', $path);
        }

        // add the root composer.json
        $root = self::readKey('autoload.psr-4') + self::readKey('autoload-dev.psr-4');

        return self::removedIgnored($result + $root);
    }

    private static function normalizePaths($value, $path)
    {
        foreach ($value as $namespace => $_path) {
            if (! Str::endsWith($_path, ['/'])) {
                $value[$namespace] .= '/';
            }

            $value[$namespace] = $path.$value[$namespace];
        }

        return $value;
    }

    private static function removedIgnored($mapping)
    {
        $result = [];
        $ignored = config('microscope.ignored_namespaces', []);

        foreach ($mapping as $namespace => $path) {
            if (! in_array($namespace, $ignored)) {
                $result[$namespace] = $path;
            }
        }

        return $result;
    }

    /**
     * @param $composerPath
     *
     * @return array
     */
    private static function readComposerFileData($composerPath)
    {
        $fullPath = self::$fakeComposerPath ?: app()->basePath($composerPath.'composer.json');

        if (! isset(self::$result[$fullPath])) {
            self::$result[$fullPath] = \json_decode(\file_get_contents($fullPath), true);
        }

        return self::$result[$fullPath];
    }
}
