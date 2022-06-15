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
                [$fileName, $folderPath] = FilePath::getFolderFile($absFilePath);

                if ($file && mb_strpos($fileName, $file) === false) {
                    continue;
                }

                if ($folder && mb_strpos($folderPath, $folder) === false) {
                    continue;
                }

                $paths[] = $absFilePath;
            }

            return $paths;
        } catch (Exception $e) {
            return [];
        }
    }
}
