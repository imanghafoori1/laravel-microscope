<?php

namespace Imanghafoori\LaravelMicroscope\Foundations;

use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;

trait CachedCheck
{
    public static function check(PhpFileDescriptor $file, $params = [])
    {
        if (CachedFiles::isCheckedBefore(self::$cacheKey, $file)) {
            return;
        }

        $hasErrors = self::performCheck($file, $params);

        if ($hasErrors === false) {
            CachedFiles::put(self::$cacheKey, $file);
        }
    }
}
