<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Exception;
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
        } catch (Exception $e) {
            return [];
        }
    }
}
