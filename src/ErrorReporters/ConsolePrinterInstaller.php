<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Illuminate\Support\Str;

class ConsolePrinterInstaller
{
    protected static function finishCommand($command)
    {
        $errorPrinter = ErrorPrinter::singleton();
        $errorPrinter->printer = $command->getOutput();

        $commandName = class_basename($command);
        $commandType = Str::after($commandName, 'Check');
        $commandType = strtolower($commandType);
        $commandType = str_replace('command', '', $commandType);

        if (! $errorPrinter->logErrors) {
            return;
        }
        $errorCount = 0;
        if ($errorPrinter->hasErrors() || $errorPrinter->pended) {
            $errorCount = $errorPrinter->count;
            $lastTimeCount = cache()->get(self::getKey($commandType), null);

            $errorCount && $command->getOutput()->writeln(PHP_EOL.$errorCount.' errors found for '.$commandType);
            $errorPrinter->logErrors();
            if (! is_null($lastTimeCount)) {
                $_msg2 = PHP_EOL.self::printErrorCount($lastTimeCount, $commandType, $errorCount);
                $_msg2 && $command->info($_msg2);
            }
        } else {
            $command->info(PHP_EOL.'All '.$commandType.' are correct!');
        }
        cache()->set(self::getKey($commandType), $errorCount);

        $errorPrinter->printTime();
    }

    protected static function getKey($commandType)
    {
        return "__microscope__$commandType-count";
    }

    protected static function printErrorCount($lastTimeCount, $commandType, $errorCount)
    {
        $lastTimeError = $commandType.' errors, compared to the last run.';
        if ($errorCount > $lastTimeCount) {
            return ' +'.($errorCount - $lastTimeCount).' new '.$lastTimeError;
        } elseif ($errorCount < $lastTimeCount) {
            return ' -'.($lastTimeCount - $errorCount).' less '.$lastTimeError;
        }
    }
}
