<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorTypes\BladeFile;
use Imanghafoori\LaravelMicroscope\ErrorTypes\CompactCall;
use Imanghafoori\LaravelMicroscope\ErrorTypes\ddFound;
use Imanghafoori\LaravelMicroscope\ErrorTypes\EnvFound;
use Imanghafoori\LaravelMicroscope\ErrorTypes\RouteDefinitionConflict;

class ConsolePrinterInstaller
{
    protected static function finishCommand($command)
    {
        /**
         * @var $errorPrinter ErrorPrinter
         */
        $errorPrinter = app(ErrorPrinter::class);
        $errorPrinter->printer = $command->getOutput();

        $commandName = class_basename($command);
        $commandType = Str::after($commandName, 'Check');
        $commandType = strtolower($commandType);

        if (! $errorPrinter->logErrors) {
            return;
        }

        if (($errorCount = $errorPrinter->hasErrors()) || $errorPrinter->pended) {
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

    public static function boot()
    {
        Event::listen(BladeFile::class, function (BladeFile $event) {
            $data = $event->data;
            $msg = 'Blade does not exist';

            app(ErrorPrinter::class)->view(
                $data['absPath'],
                $msg,
                $data['lineNumber'],
                $data['name']
            );
        });

        Event::listen(ddFound::class, function (ddFound $event) {
            $data = $event->data;
            app(ErrorPrinter::class)->simplePendError(
                $data['absPath'],
                $data['lineNumber'],
                $data['name'],
                'ddFound',
                'Debug function found: '
            );
        });

        self::compactCall();

        Event::listen(RouteDefinitionConflict::class, function ($e) {
            app(ErrorPrinter::class)->routeDefinitionConflict(
                $e->data['poorRoute'],
                $e->data['bullyRoute'],
                $e->data['info']
            );
        });

        Event::listen(EnvFound::class, function (EnvFound $event) {
            $data = $event->data;
            app(ErrorPrinter::class)->simplePendError(
                $data['absPath'],
                $data['lineNumber'],
                $data['name'],
                'envFound',
                'env() function found: '
            );
        });

        Event::listen('microscope.finished.checks', function ($command) {
            self::finishCommand($command);
        });
    }

    private static function compactCall()
    {
        Event::listen(CompactCall::class, function ($event) {
            $data = $event->data;

            app(ErrorPrinter::class)->compactError(
                $data['absPath'],
                $data['lineNumber'],
                $data['name'],
                'CompactCall',
                'compact() function call has problems man ! ');
        });
    }

    protected static function printErrorCount($lastTimeCount, $commandType, $errorCount)
    {
        $lastTimeError = $commandType.' errors, compared to the last run.';
        if (($errorCount > $lastTimeCount)) {
            return ' +'.($errorCount - $lastTimeCount).' new '.$lastTimeError;
        } elseif ($errorCount < $lastTimeCount) {
            return ' -'.($lastTimeCount - $errorCount).' less '.$lastTimeError;
        }
    }
}
