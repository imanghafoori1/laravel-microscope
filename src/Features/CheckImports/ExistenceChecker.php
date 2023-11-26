<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Throwable;

class ExistenceChecker
{
    public static function check($class, $absFilePath): bool
    {
        if (! self::isAbsent($class) || \function_exists($class)) {
            return true;
        }

        try {
            require_once $absFilePath;
        } catch (Throwable $e) {
            return false;
        }

        if (! self::isAbsent($class) || \function_exists($class)) {
            return true;
        }

        return false;
    }

    private static function isAbsent($class)
    {
        return ! class_exists($class) && ! interface_exists($class) && ! trait_exists($class) && ! (function_exists('enum_exists') && enum_exists($class));
    }
}
