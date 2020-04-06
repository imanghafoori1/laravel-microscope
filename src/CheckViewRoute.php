<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Facades\View;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class CheckViewRoute
{
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function check()
    {
        $hints = View::getFinder()->getHints();
        unset(
            $hints["notifications"],
            $hints["pagination"]
        );
        foreach ($hints as $nameSpace => $paths) {
            $this->checkPaths($paths);
        }
        $this->checkPaths(View::getFinder()->getPaths());
    }

    protected function getNextToken(array $tokens, &$next)
    {
        ++$next;
        $nextToken = $tokens[$next];
        if ($nextToken[0] == T_WHITESPACE) {
            ++$next;
            $nextToken = $tokens[$next] ;
        }

        return $nextToken;
    }

    public function checkConfigPaths($paths)
    {
        foreach ($paths as $path) {
            $files = (new Finder)->files()->in($path);

            foreach ($files as $blade) {
                /**
                 * @var \Symfony\Component\Finder\SplFileInfo $blade
                 */
                $content = file_get_contents($blade->getRealPath());
                $tokens = token_get_all($content);

                $classes = ParseUseStatement::findUseStatements($tokens);


                foreach($classes as $class) {
                    if (! class_exists($class[0])) {
                        app(ErrorPrinter::class)->print('wrong import at: '. $blade->getRealPath());
                    }
                }

                return ;
            }
        }
    }

    /**
     * @param $paths
     *
     * @return int|string
     */
    public function checkPaths($paths)
    {
        foreach ($paths as $path) {
            $files = (new Finder)->files()->in($path);

            foreach ($files as $blade) {
                /**
                 * @var \Symfony\Component\Finder\SplFileInfo $blade
                 */
                $content = file_get_contents($blade->getRealPath());
                $tokens = token_get_all(app('blade.compiler')->compileString($content));
                $classes = ParseUseStatement::findClassReferences($tokens);

                foreach($classes as $class) {
                    if (! class_exists($class['class']) && ! interface_exists($class['class'])) {
                        app(ErrorPrinter::class)->bladeImport($class, $blade);
                    }
                }

                foreach ($tokens as $i => $token) {
                    $next = $i;
                    $handleRoute = function ($nextToken, $blade) {
                        if ($nextToken[0] != T_CONSTANT_ENCAPSED_STRING) {
                            return;
                        }

                        $value = $nextToken[1];
                        $rName = app('router')->getRoutes()->getByName($value);
                        if (is_null($rName)) {
                            $this->printError($value, $blade, $nextToken);
                        }
                    };

                    $this->checkGlobalFunctionCall($token, 'route', $tokens, $handleRoute, $blade, $next);
                }
            }
        }
    }

    /**
     * @param $value
     * @param  \Symfony\Component\Finder\SplFileInfo  $blade
     * @param $nextToken
     */
    protected function printError($value, SplFileInfo $blade, $nextToken)
    {
        $p = app(ErrorPrinter::class);
        $p->print("route name $value does not exist: ");
        $p->print('route('.$value.')    <====   is wrong');
        $p->print('file name: '.$blade->getFilename());
        $p->print('line: '.$nextToken[2]);
    }

    protected function checkGlobalFunctionCall($token, string $funcName, array &$tokens, \Closure $handleRoute, SplFileInfo $blade, $next)
    {
        if ($this->isObjectMaking($tokens, $next) || $this->isFunctionDefinition($tokens, $next)) {
            return ;
        }

        if (! is_array($token) || $token[1] != $funcName) {
            return null;
        }

        $nextToken = $this->getNextToken($tokens, $next);
        if ($nextToken != '(') {
            return null;
        }

        $nextToken = $this->getNextToken($tokens, $next);
        $handleRoute($nextToken, $blade);
    }

    private function isObjectMaking(array $tokens, $next)
    {
        $pToken = $tokens[$next - 2] ?? [''] ;

        return $pToken[0] === T_NEW;
    }

    private function isFunctionDefinition(array $tokens, $next)
    {
        $pToken = $tokens[$next - 2] ?? [''] ;

        return $pToken[0] === T_FUNCTION;
    }
}
