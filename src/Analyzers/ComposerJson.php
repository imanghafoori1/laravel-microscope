<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class ComposerJson
{
    private static $result = [];

    public static function readKey($key)
    {
        if (isset(self::$result[$key])) {
            return self::$result[$key];
        }

        $composer = json_decode(file_get_contents(app()->basePath('composer.json')), true);

        self::$result[$key] = (array) data_get($composer, $key);

        return self::$result[$key];
    }

    public static function readAutoload()
    {
        return self::readKey('autoload.psr-4') + self::readKey('autoload-dev.psr-4');
    }
}
