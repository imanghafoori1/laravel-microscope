<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Closure;
use Imanghafoori\LaravelMicroscope\Features\SearchReplace\CachedFiles;

class Cache
{
    /**
     * @var string
     */
    public static $cacheFileName;

    /**
     * @var array<string, mixed>
     */
    public static $cache = [];

    public static function getForever($md5, Closure $refFinder)
    {
        return self::$cache[$md5] ?? (self::$cache[$md5] = $refFinder());
    }

    public static function writeCacheContent(): void
    {
        $cache = self::$cache;

        if (! $cache) {
            return;
        }

        $folder = CachedFiles::getFolderPath();
        ! is_dir($folder) && mkdir($folder);
        $content = CachedFiles::getCacheFileContents($cache);
        $path = $folder.self::$cacheFileName;
        file_exists($path) && chmod($path, 0777);
        file_put_contents($path, $content);
        self::$cache = [];
    }

    public static function loadToMemory($cacheFileName)
    {
        self::$cacheFileName = $cacheFileName;
        if (self::$cache) {
            // is already loaded
            return;
        }

        $path = CachedFiles::getFolderPath().$cacheFileName;

        if (file_exists($path)) {
            self::$cache = (require $path) ?: [];
        }
    }
}
