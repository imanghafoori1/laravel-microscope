<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

class Paths
{
    public static function getAbsFilePaths($dirs)
    {
        if (! $dirs) {
            return [];
        }
        try {
            $files = Finder::create()->files()->name('*.php')->in($dirs);

            $paths = [];
            foreach ($files as $f) {
                $paths[] = $f->getRealPath();
            }

            return $paths;
        } catch (DirectoryNotFoundException $e) {
            return [];
        }
    }
}
