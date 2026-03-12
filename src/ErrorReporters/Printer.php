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
        foreach ($messages as $message) {
            if (is_string($message)) {
                $output->write($message);
            } else {
                self::printAll($message, $output);
            }
        }
    }
}
