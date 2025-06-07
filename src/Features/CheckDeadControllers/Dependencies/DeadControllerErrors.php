<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDeadControllers\Dependencies;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class DeadControllerErrors
{
    public static function printErrors(array $actions, $absFilePath)
    {
        $errorPrinter = ErrorPrinter::singleton();

        $header = 'No route is defined for controller action:';
        foreach ($actions as $action) {
            $errorPrinter->simplePendError($action[1], $absFilePath, $action[0], 'routelessCtrl', $header);
        }
    }
}
