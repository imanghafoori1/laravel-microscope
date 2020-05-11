<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Symfony\Component\Finder\Finder;

class Paths
{
    public static function getPathsList($path)
    {
        $files = Finder::create()->files()->name('*.php')->in($path);

        $paths = [];
        foreach ($files as $f) {
            $paths[] = $f->getRealPath();
        }

        return $paths;
    }
}
