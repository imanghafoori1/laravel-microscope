<?php

namespace Imanghafoori\LaravelMicroscope\Traits;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

trait LogsErrors
{
    /**
     * Shows the status of a successful or failed check.
     *
     * @param  ErrorPrinter  $errorPrinter
     */
    protected function finishCommand(ErrorPrinter $errorPrinter)
    {
        $commandName = class_basename($this);
        $commandType = Str::after($commandName, 'Check');
        $commandType = $this->commandType ?? strtolower($commandType);

        if (! $errorPrinter->logErrors) {
            return;
        }

        if (($errorCount = $errorPrinter->hasErrors()) || $errorPrinter->pended) {
            $errorCount && $this->warn(PHP_EOL.$errorCount.' errors found for '.$commandType);

            $errorPrinter->logErrors();
        } elseif (property_exists($this, 'customMsg')) {
            $this->info(PHP_EOL.$this->customMsg);
        } else {
            $this->info(PHP_EOL.'All '.$commandType.' are correct!');
        }
    }
}
