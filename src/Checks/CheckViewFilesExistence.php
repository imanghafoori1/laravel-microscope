<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckViewFilesExistence
{
    public function check($tokens, $absPath)
    {
        $tCount = count($tokens);
        for ($i = 0; $i < $tCount; $i++) {
            if (! $this->isEnvMake($tokens, $i)) {
                continue;
            }

            $viewName = trim($tokens[$i + 4][1], '\'\"');
            if (! View::exists($viewName)) {
                $this->error($tokens, $absPath, $i);
            }
            $i = $i + 5;
        }
    }

    private function isEnvMake($tokens, $i)
    {
        $methods = [
            'make',
            'first',
            'renderWhen',
        ];

        // checks for this syntax: $__env->make('myViewFile', ...
        return ($tokens[$i][1] ?? null) == '$__env'
            && in_array($tokens[$i + 2][1] ?? null, $methods)
            && ($tokens[$i + 4][0] ?? '') == T_CONSTANT_ENCAPSED_STRING
            && ($tokens[$i + 5] ?? null) == ',';
    }

    /**
     * @param  array  $tokens
     * @param $absPath
     * @param  int  $i
     */
    private function error($tokens, $absPath, $i)
    {
        $p = app(ErrorPrinter::class);
        $p->print('included view: '.$tokens[$i + 4][1].' does not exist in blade file');
        $p->printLink($absPath, $tokens[$i + 4][2]);
        $p->end();
    }
}
