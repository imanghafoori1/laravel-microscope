<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Throwable;

class ExistenceChecker
{
    public static function check($import, $absFilePath): bool
    {
        if (self::entityExists($import)) {
            return true;
        }

        try {
            require_once $absFilePath;
        } catch (Throwable $e) {
            return false;
        }

        if (self::entityExists($import)) {
            return true;
        }

        return false;
    }

    private static function entityExists($import)
    {
        return class_exists($import) ||
            interface_exists($import) ||
            trait_exists($import) ||
            function_exists($import) ||
            (function_exists('enum_exists') && enum_exists($import));
    }
}
