<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Imanghafoori\LaravelMicroscope\Foundations\Console;
use Imanghafoori\LaravelMicroscope\Foundations\ConsoleWriter;

class Printer implements ConsoleWriter
{
    private $output;

    public function __construct($output)
    {
        $this->output = $output;
    }

    public function writeln($string)
    {
        $this->output->writeln($string);
    }

    /**
     * @param  array|\Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\AutoloadStats  $messages
     * @return void
     */
    public static function printAll($messages): void
    {
        foreach ($messages as $message) {
            if (is_string($message)) {
                Console::getInstance()->write($message);
            } else {
                self::printAll($message);
            }
        }
    }
}
