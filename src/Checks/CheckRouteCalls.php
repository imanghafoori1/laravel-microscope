<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Imanghafoori\LaravelMicroscope\Analyzers\GlobalFunctionCall;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckRouteCalls
{
    public function check($tokens, $absFilePath)
    {
        $handleRoute = function ($line, $routeName, $absPath) {
            $matchedRoute = app('router')->getRoutes()->getByName(trim($routeName, '\'\"'));
            if (is_null($matchedRoute)) {
                $this->printError($routeName, $absPath, $line);
            }
        };

        // we skip the very first tokens: '<?php '
        $i = 4;
        // we skip the very end of the file.
        $total = count($tokens) - 3;
        while ($i < $total) {
            [$param1, $matchedToken] = GlobalFunctionCall::detect('route', $tokens, $i);
            $param1 && $handleRoute($matchedToken[2], $param1, $absFilePath);
            $i++;
        }

        return $tokens;
    }

    /**
     * @param $value
     * @param $absPath
     * @param $lineNumber
     */
    protected function printError($value, $absPath, $lineNumber)
    {
        $p = app(ErrorPrinter::class);
        $p->route(null, "route name $value does not exist: ", "route($value)   <====   is wrong", $absPath, $lineNumber);
    }
}
