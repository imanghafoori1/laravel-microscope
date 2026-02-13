<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDeadControllers\Dependencies;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class DeadControllerErrorHandler
{
    public static function handle(array $actions, $file)
    {
        $errorPrinter = ErrorPrinter::singleton();

        $header = 'No route is defined for controller action:';
        foreach ($actions as $action) {
            $errorPrinter->simplePendError($action[1], $file, $action[0], 'routelessCtrl', $header);
        }
    }
}
