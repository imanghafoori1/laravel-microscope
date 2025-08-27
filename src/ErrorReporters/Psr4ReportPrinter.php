<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

class Psr4ReportPrinter
{
    /**
     * @param  array<int, array<int, string|\Generator<int, string>>>  $messages
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
        $output->writeln('');
    }
}
