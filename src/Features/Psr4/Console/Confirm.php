<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\Console;

class Confirm
{
    public static $askTime = 0;

    public static function ask($command, $correctNamespace)
    {
        $time = microtime(true);
        try {
            return $command->getOutput()->confirm(self::getQuestion($correctNamespace), true);
        } finally {
            self::$askTime += (microtime(true) - $time);
        }
    }

    private static function getQuestion($replacement)
    {
        return "Do you want to change it to: <fg=blue>$replacement</>";
    }
}
