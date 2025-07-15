<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4;

class Confirm
{
    public static $askTime = 0;

    public static function ask($command, $correctNamespace)
    {
        if ($command->option('nofix')) {
            return false;
        }

        if ($command->option('force')) {
            return true;
        }

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
