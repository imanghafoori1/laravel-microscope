<?php

namespace Imanghafoori\LaravelMicroscope\Foundations;

use Imanghafoori\LaravelMicroscope\Foundations\Reports\LineSeperator;

class Color
{
    private static $color = true;

    public static function yellow($msg)
    {
        return self::color($msg, __FUNCTION__);
    }

    public static function boldYellow($msg)
    {
        return self::$color ? '<options=bold;fg=yellow>'.$msg.'</>' : $msg;
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
        return self::$color ? '<fg='.$color.'>'.$msg.'</>' : $msg;
    }
}