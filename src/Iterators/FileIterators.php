<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;

class FileIterators
{
    /**
     * @param  string[]  $paths
     * @param  \Closure  $paramProvider
     * @return void
     */
    public static function checkFilePaths($paths, $paramProvider, $checks)
    {
        foreach ($paths as $dir => $absFilePaths) {
            foreach ((array) $absFilePaths as $absFilePath) {
                $tokens = token_get_all(file_get_contents($absFilePath));
                foreach ($checks as $check) {
                    $check::check($tokens, $absFilePath, $paramProvider($tokens));
                }
            }
        }
    }

    /**
     * @return array<string, string[]>
     */
    public static function getLaravelFolders()
    {
        return [
            'config' => LaravelPaths::configDirs(),
            'migrations' => LaravelPaths::migrationDirs(),
        ];
    }

    /**
     * @param  array<string, string[]>  $dirsList
     * @param  $paramProvider
     * @param  string  $file
     * @param  string  $folder
     * @param  array  $checks
     * @return array<string, array<string, array<string, string[]>>>
     */
    public static function checkFolders($dirsList, $paramProvider, $file, $folder, $checks)
    {
        $files = [];
        foreach ($dirsList as $listName => $dirs) {
            $filePaths = Paths::getAbsFilePaths($dirs, $file, $folder);
            self::checkFilePaths($filePaths, $paramProvider, $checks);

            foreach ($filePaths as $dir => $filePathList) {
                $files[$listName][$dir] = $filePathList;
            }
        }

        return $files;
    }
}
