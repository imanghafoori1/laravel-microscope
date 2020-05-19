<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Illuminate\Support\Str;

class ComposerJson
{
    private static $result = [];

    public static function readKey($key)
    {
        if (isset(self::$result[$key])) {
            return self::$result[$key];
        }

        $composer = json_decode(file_get_contents(app()->basePath('composer.json')), true);

        $value = (array) data_get($composer, $key, []);

        if (in_array($key, ['autoload.psr-4', 'autoload-dev.psr-4'])) {
            $value = self::normalizePaths($value);
        }

        return self::$result[$key] = $value;
    }

    public static function readAutoload()
    {
        return self::readKey('autoload.psr-4') + self::readKey('autoload-dev.psr-4');
    }

    private static function normalizePaths($value)
    {
        foreach ($value as $namespace => $path) {
            if (! Str::endsWith($path, ['/'])) {
                $value[$namespace] .= '/';
            }
        }

        return $value;
    }
}
