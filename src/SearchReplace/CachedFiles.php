<?php

namespace Imanghafoori\LaravelMicroscope\SearchReplace;

use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class CachedFiles
{
    public static $folderPath;

    private static $cache = [];

    private static $cacheChange = [];

    private static $fileExists = [];

    private function __construct()
    {
        //
    }

    public static function isCheckedBefore($patternKey, PhpFileDescriptor $file): bool
    {
        if (self::cacheIsLoadedIntoMemory($patternKey)) {
            return self::checkIsInCache($patternKey, $file);
        }

        $path = self::getFolderPath().$patternKey.'.php';

        if (! isset(self::$fileExists[$patternKey])) {
            self::$fileExists[$patternKey] = file_exists($path);
        }

        // If there is no cache file:
        if (self::$fileExists[$patternKey] === false) {
            return false;
        }

        // If there is a cache file but not loaded yet:
        self::loadIntoMemory($patternKey, $path);

        return self::checkIsInCache($patternKey, $file);
    }

    private static function cacheIsLoadedIntoMemory($patternKey): bool
    {
        return isset(self::$cache[$patternKey]);
    }

    public static function getFolderPath(): string
    {
        return self::$folderPath;
    }

    private static function checkIsInCache($patternKey, PhpFileDescriptor $file)
    {
        return $file->getFileName() === self::readFromMemoryCache($patternKey, $file->getMd5());
    }

    /**
     * @param  string  $patternKey
     * @param  string  $path
     * @return void
     */
    private static function loadIntoMemory($patternKey, $path): void
    {
        self::$cache[$patternKey] = require $path;
    }

    public static function put($patternKey, PhpFileDescriptor $file)
    {
        self::$cacheChange[$patternKey] = true;
        self::$cache[$patternKey][$file->getMd5()] = $file->getFileName();
    }

    public static function writeCacheFiles()
    {
        $folder = self::getFolderPath();

        if (! is_dir($folder)) {
            mkdir($folder, 0777);
        }

        foreach (self::$cache as $patternKey => $fileMd5) {
            // Here we avoid writing the exact same content to the file.
            if (self::$cacheChange[$patternKey] ?? '') {
                $path = $folder.$patternKey.'.php';
                is_file($path) && chmod($path, 0777);
                file_put_contents($path, self::getCacheFileContents($fileMd5));
            }
        }

        self::$cache = self::$cacheChange = [];
    }

    private static function readFromMemoryCache($patternKey, $md5)
    {
        return self::$cache[$patternKey][$md5] ?? '';
    }

    public static function getCacheFileContents($fileHashes): string
    {
        return '<?php '.PHP_EOL.'return '.var_export($fileHashes, true).';';
    }
}
