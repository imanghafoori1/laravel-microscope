<?php

namespace Imanghafoori\LaravelMicroscope\SearchReplace;

use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class CachedFiles
{
    private static $cache = [];

    private static $cacheChange = [];

    private function __construct()
    {
        //
    }

    public static function isCheckedBefore($patternKey, PhpFileDescriptor $file): bool
    {
        if (self::cacheIsLoaded($patternKey)) {
            return self::check($patternKey, $file);
        }

        $path = self::getPathForPattern().$patternKey.'.php';

        // If there is no cache file:
        if (! file_exists($path)) {
            return false;
        }

        // If there is a cache file but not loaded yet:
        self::load($patternKey, $path);

        return self::check($patternKey, $file);
    }

    private static function cacheIsLoaded($patternKey): bool
    {
        return isset(self::$cache[$patternKey]);
    }

    private static function getPathForPattern(): string
    {
        $ds = DIRECTORY_SEPARATOR;

        return storage_path('framework'.$ds.'cache'.$ds.'microscope'.$ds);
    }

    /**
     * Checks to see if the pattern is checked on the given file or not.
     *
     * @param  string  $PatternKey
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor  $file
     * @return bool
     */
    private static function check($PatternKey, PhpFileDescriptor $file)
    {
        return $file->getFileName() === self::readFromCache($PatternKey, $file->getMd5());
    }

    /**
     * @param  string  $patternKey
     * @param  string  $path
     * @return void
     */
    private static function load($patternKey, $path): void
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
        $folder = self::getPathForPattern();

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
    }

    private static function readFromCache($patternKey, $md5)
    {
        return self::$cache[$patternKey][$md5] ?? '';
    }

    private static function getCacheFileContents($fileHashes): string
    {
        return '<?php '.PHP_EOL.'return '.var_export($fileHashes, true).';';
    }
}
