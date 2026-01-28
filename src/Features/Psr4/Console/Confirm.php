<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\Console;

use Imanghafoori\LaravelMicroscope\Foundations\Color;

class Confirm
{
    /**
     * @var positive-int
     */
    public static $askTime = 0;

    /**
     * @param \Illuminate\Console\Command $command
     * @param string $correctNamespace
     * @return bool
     */
    public static function ask($command, $correctNamespace)
    {
        $time = microtime(true);
        try {
            return $command->getOutput()->confirm(self::getQuestion($correctNamespace), true);
        } finally {
            self::$askTime += (microtime(true) - $time);
        }
    }

    /**
     * @param string $replacement
     * @return string
     */
    private static function getQuestion($replacement)
    {
        $replacement = Color::blue($replacement);

        return "Do you want to change it to: $replacement";
    }
}
