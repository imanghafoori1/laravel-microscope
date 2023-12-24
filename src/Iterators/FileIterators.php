<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;

class FileIterators
{
    /**
     * @param  \Generator  $paths
     * @param  \Closure  $paramProvider
     * @return \Generator
     */
    public static function checkFilePaths($paths, $paramProvider, $checks)
    {
        foreach ($paths as $dir => $absFilePaths) {
            $c = 0;
            if (is_string($absFilePaths)) {
                $absFilePaths = [$absFilePaths];
            }
            foreach ($absFilePaths as $absFilePath) {
                $c++;
                $tokens = token_get_all(file_get_contents($absFilePath));
                foreach ($checks as $check) {
                    $check::check($tokens, $absFilePath, $paramProvider($tokens));
                }
            }
            yield $dir => $c;
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
     * @return \Generator<string, array<string, array<string, string[]>>>
     */
    public static function checkFolders($dirsList, $paramProvider, $file, $folder, $checks)
    {
        foreach ($dirsList as $listName => $dirs) {
            $filePaths = Paths::getAbsFilePaths($dirs, $file, $folder);
            yield $listName => self::checkFilePaths($filePaths, $paramProvider, $checks);
        }
    }
}
