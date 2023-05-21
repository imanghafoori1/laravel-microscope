<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use ImanGhafoori\ComposerJson\ComposerJson as Composer;

class ComposerJson
{
    public static function make(): Composer
    {
        return resolve(Composer::class);
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
            foreach ($files['autoload'] as $f) {
                $allFiles[] = $p.'/'.$f;
            }
            foreach ($files['autoload-dev'] as $f) {
                $allFiles[] = $p.'/'.$f;
            }
        }

        return $allFiles;
    }
}
