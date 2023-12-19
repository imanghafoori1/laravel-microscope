<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Exception;
use Symfony\Component\Finder\Finder;

class Paths
{
    /**
     * @param  $dirs
     * @param  null|string  $includeFileName
     * @param  null|string  $includeFolder
     * @return \iterable
     */
    public static function getAbsFilePaths($dirs, $includeFileName = null, $includeFolder = null)
    {
        if (! $dirs) {
            return [];
        }

        $includeFolder && ($includeFolder = str_replace('\\', '/', $includeFolder));
        is_string($dirs) && ($dirs = [$dirs]);
        foreach ($dirs as $dir) {
            yield $dir => self::getPathsInDir($dir, $includeFileName, $includeFolder);
        }
    }

    /**
     * @param  $dir
     * @param  $fileName
     * @param  $folder
     * @return \iterable
     */
    private static function getPathsInDir($dir, $fileName, $folder)
    {
        try {
            $files = Finder::create()->files()->name(($fileName ?: '*').'.php')->in($dir);
            foreach ($files as $absFilePath => $f) {
                if (FilePath::contains($absFilePath, $fileName, $folder)) {
                    yield $absFilePath;
                }
            }
        } catch (Exception $e) {
            return [];
        }
    }
}
