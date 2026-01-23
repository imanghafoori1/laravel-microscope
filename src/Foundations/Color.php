<?php

namespace Imanghafoori\LaravelMicroscope\Foundations;

use Imanghafoori\LaravelMicroscope\Foundations\Reports\LineSeperator;

class Color
{
    public static $color = true;

    public static function yellow($msg)
    {
        return self::color($msg, __FUNCTION__);
    }

    public static function boldYellow($msg)
    {
        if (self::$color === false) {
            return $msg;
        }

        return "<options=bold;fg=yellow>$msg</>";
    }

    public static function red($msg)
    {
        return self::color($msg, __FUNCTION__);
    }

    public static function blue($msg)
    {
        return self::color($msg, __FUNCTION__);
    }

    public static function green($msg)
    {
        return self::color($msg, __FUNCTION__);
    }

    public static function white($msg)
    {
        return self::color($msg, __FUNCTION__);
    }

    public static function gray($msg)
    {
        return self::color($msg, LineSeperator::$color);
    }

    public static function cyan($msg)
    {
        return self::color($msg, __FUNCTION__);
    }

    private static function color($msg, $color)
    {
        if (self::$color === false) {
            return $msg;
        }

        return "<fg=$color>$msg</>" ;
    }
}