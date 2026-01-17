<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Closure;
use Imanghafoori\LaravelMicroscope\Features\SearchReplace\CachedFiles;

class ImportCache
{
    public static $cache = [];

    public static function getForever($md5, Closure $refFinder)
    {
        return self::$cache[$md5] ?? (self::$cache[$md5] = $refFinder());
    }

    public static function writeCacheContent(array $cache): void
    {
        $folder = CachedFiles::getFolderPath();
        ! is_dir($folder) && mkdir($folder);
        $content = CachedFiles::getCacheFileContents($cache);
        $path = $folder.'check_imports.php';
        file_exists($path) && chmod($path, 0777);
        file_put_contents($path, $content);
    }
}
