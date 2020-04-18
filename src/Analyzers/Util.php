<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class Util
{
    private static $result = [];

    public static function parseComposerJson($key)
    {
        if (isset(self::$result[$key])) {
            return self::$result[$key];
        }

        $composer = json_decode(file_get_contents(app()->basePath('composer.json')), true);

        self::$result[$key] = (array) data_get($composer, $key);

        return self::$result[$key];
    }
}
