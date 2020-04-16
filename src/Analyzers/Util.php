<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class Util
{
    public static function parseComposerJson($key)
    {
        $composer = json_decode(file_get_contents(app()->basePath('composer.json')), true);

        return (array) data_get($composer, $key);
    }
}
