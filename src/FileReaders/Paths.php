<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Exception;
use Symfony\Component\Finder\Finder;

class Paths
{
    public static function getAbsFilePaths($dirs, $file = null, $folder = null)
    {
        if (! $dirs) {
            return [];
        }

        $folder && ($folder = str_replace('\\', '/', $folder));
        try {
            $files = Finder::create()->files()->name('*.php')->in($dirs);

            $paths = [];
            foreach ($files as $f) {
                $absFilePath = $f->getRealPath();
                FilePath::contains($absFilePath, $file, $folder);

                $paths[] = $absFilePath;
            }

            return $paths;
        } catch (Exception $e) {
            return [];
        }
    }
}
