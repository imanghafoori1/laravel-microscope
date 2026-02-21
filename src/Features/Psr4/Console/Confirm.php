<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\Console;

use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\Console;

class Confirm
{
    /**
     * @var positive-int
     */
    public static $askTime = 0;

    /**
     * @param  string  $correctNamespace
     * @return bool
     */
    public static function ask($correctNamespace)
    {
        $time = microtime(true);
        try {
            return Console::confirm(self::getQuestion($correctNamespace));
        } finally {
            self::$askTime += (microtime(true) - $time);
        }
    }

    /**
     * @param  string  $replacement
     * @return string
     */
    private static function getQuestion($replacement)
    {
        $replacement = Color::blue($replacement);

        return "Do you want to change it to: $replacement";
    }
}
