<?php

namespace Imanghafoori\LaravelMicroscope\Foundations;

use Imanghafoori\LaravelMicroscope\Features\SearchReplace\CachedFiles;

trait CachedCheck
{
    public static $cache = true;

    public static function check(PhpFileDescriptor $file)
    {
        if (self::$cache && CachedFiles::isCheckedBefore(self::$cacheKey, $file)) {
            return;
        }

        $hasErrors = self::performCheck($file);

        if ($hasErrors) {
            return;
        }

        self::$cache && CachedFiles::put(self::$cacheKey, $file);
    }
}
