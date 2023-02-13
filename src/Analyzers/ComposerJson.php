<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use ImanGhafoori\ComposerJson\ComposerJson as Composer;

class ComposerJson
{
    public static function readAutoload($purgeAutoload = false)
    {
        $psr4Autoloads = Composer::make(base_path())->readAutoload($purgeAutoload);

        return self::removedIgnored($psr4Autoloads, config('microscope.ignored_namespaces', []));
    }

    public static function readAutoloadFiles()
    {
        $basepath = base_path();
        $psr4Autoloads = Composer::make($basepath)->readAutoloadFiles();

        $allFiles = [];
        foreach ($psr4Autoloads as $path => $files) {
            $p = $basepath.'/'.trim($path, '/');
            foreach ($files['autoload'] as $f) {
                $allFiles[] = $p.'/'.$f;
            }
            foreach ($files['autoload-dev'] as $f) {
                $allFiles[] = $p.'/'.$f;
            }
        }

        return $allFiles;
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
}
