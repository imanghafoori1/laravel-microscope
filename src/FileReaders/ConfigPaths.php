<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Symfony\Component\Finder\Finder;

class ConfigPaths
{
    public static function get()
    {
        $configFiles = (new Finder)->files()->name('*.php')->in(app()->configPath());

        $paths = [];

        foreach ($configFiles as $confFile) {
            $paths[] = $confFile->getRealPath();
        }

        return $paths;
    }
}
