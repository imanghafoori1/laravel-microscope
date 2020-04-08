<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Symfony\Component\Finder\SplFileInfo;
use Imanghafoori\LaravelMicroscope\ErrorPrinter;

class CheckRouteCalls
{
    function check(array $tokens, SplFileInfo $blade)
    {
        $handleRoute = function ($nextToken, $blade) {
            if ($nextToken[0] != T_CONSTANT_ENCAPSED_STRING) {
                return;
            }

            $value = $nextToken[1];

            $rName = app('router')->getRoutes()->getByName(trim($value, '\'\"'));
            if (is_null($rName)) {
                $this->printError($value, $blade, $nextToken);
            }
        };

        foreach ($tokens as $i => $token) {
            $next = $i;
            $this->checkGlobalFunctionCall($token, 'route', $tokens, $handleRoute, $blade, $next);
        }

        return $tokens;
    }


    /**
     * @param $value
     * @param  \Symfony\Component\Finder\SplFileInfo  $blade
     * @param $nextToken
     */
    protected function printError($value, SplFileInfo $blade, $nextToken)
    {
        $p = app(ErrorPrinter::class);
        $p->print("route name '$value' does not exist: ");
        $p->print("route($value)   <====   is wrong");
        $p->printLink($blade->getPathname(), $nextToken[2]);
        $p->end();
    }

    protected function checkGlobalFunctionCall($token, string $funcName, array &$tokens, \Closure $handleRoute, SplFileInfo $blade, $next)
    {
        if ($this->isObjectMaking($tokens, $next) || $this->isFunctionDefinition($tokens, $next)) {
            return;
        }

        if (! is_array($token) || $token[1] != $funcName) {
            return;
        }

        $nextToken = $this->getNextToken($tokens, $next);
        if ($nextToken != '(') {
            return;
        }

        $nextToken = $this->getNextToken($tokens, $next);
        $handleRoute($nextToken, $blade);
    }

    private function isObjectMaking(array $tokens, $next)
    {
        $pToken = $tokens[$next - 2] ?? [''];

        return $pToken[0] === T_NEW;
    }

    private function isFunctionDefinition(array $tokens, $next)
    {
        $pToken = $tokens[$next - 2] ?? [''];

        return $pToken[0] === T_FUNCTION;
    }

    protected function getNextToken(array $tokens, &$next)
    {
        $next++;
        $nextToken = $tokens[$next];
        if ($nextToken[0] == T_WHITESPACE) {
            $next++;
            $nextToken = $tokens[$next];
        }

        return $nextToken;
    }
}
