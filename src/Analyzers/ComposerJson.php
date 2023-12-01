<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use ImanGhafoori\ComposerJson\ComposerJson as Composer;

class ComposerJson
{
    public static $composer;

    public static function make(): Composer
    {
        return (self::$composer)();
    }

    public static function readAutoload($purgeAutoload = false)
    {
        return self::make()->readAutoload($purgeAutoload);
    }

    public static function readAutoloadFiles()
    {
        $basePath = base_path();
        $psr4Autoloads = self::make()->readAutoloadFiles();

        $allFiles = [];
        foreach ($psr4Autoloads as $path => $files) {
            $p = $basePath.'/'.trim($path, '/');
            foreach (array_merge($files['autoload'], $files['autoload-dev']) as $f) {
                $allFiles[] = str_replace('/', DIRECTORY_SEPARATOR, $p.'/'.trim($f, '/'));
            }
        }

        return $allFiles;
    }
}
