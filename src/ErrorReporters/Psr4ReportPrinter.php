<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Imanghafoori\LaravelMicroscope\Iterators\DTO\AutoloadStats;

class Psr4ReportPrinter
{
    /**
     * @param  array|\Imanghafoori\LaravelMicroscope\Iterators\DTO\AutoloadStats  $messages
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
        $output->write(PHP_EOL);
    }
}
