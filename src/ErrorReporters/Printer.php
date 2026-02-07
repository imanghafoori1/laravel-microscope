<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\AutoloadStats;

class Printer
{
    /**
     * @param  array|\Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\AutoloadStats  $messages
     * @param  $output
     * @return void
     */
    public static function printAll($messages, $output): void
    {
        if (is_a($messages, AutoloadStats::class)) {
            $messages = $messages->stats;
        }

        foreach ($messages as $message) {
            if (is_string($message)) {
                $output->write($message);
            } else {
                self::printAll($message, $output);
            }
        }
    }
}
