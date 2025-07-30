<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

class Psr4ReportPrinter
{

    public static function printMessages($messages, $output): void
    {
        foreach ($messages as $message) {
            if (is_string($message)) {
                $output->write($message);
            } else {
                self::printMessages($message, $output);
            }
        }
        $output->writeln('');
    }
}
