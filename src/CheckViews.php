<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\Analyzers\Util;
use Imanghafoori\LaravelMicroscope\Checks\CheckRouteCalls;
use Symfony\Component\Finder\Finder;

class CheckViews
{
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @param $methods
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

        $this->checkClassesRouteCalls();
    }

    private function getNamespacedPaths()
    {
        $hints = View::getFinder()->getHints();
        unset($hints['notifications'], $hints['pagination']);

        return $hints;
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
                $content = $blade->getContents();
                $tokens = token_get_all(app('blade.compiler')->compileString($content));

                foreach ($methods as $method) {
                    call_user_func_array($method, [$tokens, $blade->getPathname()]);
                }
            }
        }
    }

    protected function checkClassesRouteCalls()
    {
        $psr4 = Util::parseComposerJson('autoload.psr-4');

        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = CheckClasses::getAllPhpFiles($psr4Path);
            foreach ($files as $classFilePath) {
                $absFilePath = $classFilePath->getRealPath();
                $tokens = token_get_all(file_get_contents($absFilePath));
                CheckRouteCalls::check($tokens, $absFilePath);
            }
        }
    }
}
