<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Facades\View;
use Symfony\Component\Finder\Finder;

class CheckViewRoute
{
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function check($methods)
    {
        $hints = $this->getNamespacedPaths();
        $hints['1'] = View::getFinder()->getPaths();
        foreach ($hints as $paths) {
            $this->checkPaths($paths, $methods);
        }
    }

    private function getNamespacedPaths()
    {
        $hints = View::getFinder()->getHints();
        unset($hints['notifications'], $hints['pagination']);

        return $hints;
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

                foreach ($classes as $class) {
                    if (! class_exists($class[0])) {
                        app(ErrorPrinter::class)->others('wrong import at: '.$blade->getRealPath());
                    }
                }

                return;
            }
        }
    }

    /**
     * @param $paths
     *
     * @param $methods
     *
     * @return int|string
     */
    public function checkPaths($paths, $methods)
    {
        foreach ($paths as $path) {
            $files = (new Finder)->files()->in($path);

            foreach ($files as $blade) {
                /**
                 * @var \Symfony\Component\Finder\SplFileInfo $blade
                 */
                $content = file_get_contents($blade->getRealPath());
                $tokens = token_get_all((app('blade.compiler')->compileString($content)));

                foreach ($methods as $method) {
                    call_user_func_array($method, [$tokens, $blade]);
                }
            }
        }
    }
}
