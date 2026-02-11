<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Closure;
use Imanghafoori\LaravelMicroscope\Features\SearchReplace\CachedFiles;

class Cache
{
    /**
     * @var string[]
     */
    public static $cacheFileNames = [];

    /**
     * @var array<string, mixed>
     */
    public static $cache = [];

    public static function getForever($md5, $key, Closure $callback)
    {
        return self::$cache[$key][$md5] ?? (self::$cache[$key][$md5] = $callback());
    }

    public static function writeCacheContent(): void
    {
        foreach (self::$cacheFileNames as $cacheFileName) {
            $cache = self::$cache[$cacheFileName] ?? null;

            if (! $cache) {
                continue;
            }

            $folder = CachedFiles::getFolderPath();
            ! is_dir($folder) && mkdir($folder);
            $content = CachedFiles::getCacheFileContents($cache);
            $path = $folder.$cacheFileName.'.php';
            file_exists($path) && chmod($path, 0777);
            file_put_contents($path, $content);
            self::$cache[$cacheFileName] = [];
        }
    }

    public static function loadToMemory($cacheFileName)
    {
        self::$cacheFileNames[] = $cacheFileName;

        if (self::$cache[$cacheFileName] ?? '') {
            // is already loaded
            return;
        }

        $path = CachedFiles::getFolderPath().$cacheFileName.'.php';

        if (file_exists($path)) {
            self::$cache[$cacheFileName] = (require $path) ?: [];
        }
    }
}
