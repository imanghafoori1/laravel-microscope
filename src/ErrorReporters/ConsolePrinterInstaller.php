<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use Imanghafoori\LaravelMicroscope\ErrorTypes\ddFound;
use Imanghafoori\LaravelMicroscope\ErrorTypes\EnvFound;
use Imanghafoori\LaravelMicroscope\ErrorTypes\BladeFile;
use Imanghafoori\LaravelMicroscope\ErrorTypes\CompactCall;
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
            $msg = $errorCount.' errors found for '.$commandType;

            $errorCount && $command->getOutput()->writeln(PHP_EOL.$msg);
            $errorPrinter->logErrors();
        } else {
            $command->info(PHP_EOL.'All '.$commandType.' are correct!');
        }

        $errorPrinter->printTime();
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
}
