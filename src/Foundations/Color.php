<?php

namespace Imanghafoori\LaravelMicroscope\Foundations;

use Imanghafoori\LaravelMicroscope\Foundations\Reports\LineSeperator;

class Color
{
    public static bool $color = true;

    /**
     * @param  string  $msg
     * @return string
     */
    public static function yellow($msg)
    {
        return self::color($msg, __FUNCTION__);
    }

    /**
     * @param  string  $msg
     * @return string
     */
    public static function boldYellow($msg)
    {
        if (self::$color === false) {
            return $msg;
        }

        return "<options=bold;fg=yellow>$msg</>";
    }

    /**
     * @param  string  $msg
     * @return string
     */
    public static function red($msg)
    {
        return self::color($msg, __FUNCTION__);
    }

    /**
     * @param  string  $msg
     * @return string
     */
    public static function blue($msg)
    {
        return self::color($msg, __FUNCTION__);
    }

    /**
     * @param  string  $msg
     * @return string
     */
    public static function green($msg)
    {
        return self::color($msg, __FUNCTION__);
    }

    /**
     * @param  string  $msg
     * @return string
     */
    public static function white($msg)
    {
        return self::color($msg, __FUNCTION__);
    }

    /**
     * @param  string  $msg
     * @return string
     */
    public static function gray($msg)
    {
        return self::color($msg, LineSeperator::$color);
    }

    /**
     * @param  string  $msg
     * @return string
     */
    public static function cyan($msg)
    {
        return self::color($msg, __FUNCTION__);
    }

    /**
     * @param  string  $msg
     * @param  string  $color
     * @return string
     */
    private static function color($msg, $color)
    {
        if (self::$color === false) {
            return $msg;
        }

        return "<fg=$color>$msg</>";
    }
}
