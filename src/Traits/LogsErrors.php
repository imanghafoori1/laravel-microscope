<?php

namespace Imanghafoori\LaravelMicroscope\Traits;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorPrinter;

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
        $commandType = strtolower($commandType);

        if ($errorCount = $errorPrinter->hasErrors()) {
            $this->error($errorCount . ' errors found for ' . $commandType);
        } else $this->info('All '.$commandType.' are correct!');
    }
}
