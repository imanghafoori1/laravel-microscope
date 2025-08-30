<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;

class ForBladeFiles implements Check
{
    /**
     * @param  array<int, class-string<\Imanghafoori\LaravelMicroscope\Check>>  $checks
     * @param  array|\Closure  $params
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return array<int, \Generator<string, int>>
     */
    public static function check($checks, $params = [], $pathDTO = null)
    {
        self::withoutComponentTags();
        $mapper = function ($paths) use ($checks, $params, $pathDTO) {
            return BladeFiles\CheckBladePaths::checkPaths($paths, $checks, $params, $pathDTO);
        };

        return Loop::map(self::getViewsPaths(), $mapper);
    }

    /**
     * @return array<string, \Generator<int, string>>
     */
    public static function getViewsPaths()
    {
        $hints = View::getFinder()->getHints();
        $hints['random_key_69471'] = View::getFinder()->getPaths();
        unset(
            $hints['notifications'],
            $hints['pagination']
        );

        return self::normalizeAndFilterVendorPaths($hints);
    }

    /**
     * @return void
     */
    private static function withoutComponentTags()
    {
        $compiler = app('microscope.blade.compiler');
        method_exists($compiler, 'withoutComponentTags') && $compiler->withoutComponentTags();
    }

    /**
     * @param  string[]  $paths
     * @return \Generator<int, string>
     */
    private static function filterPaths($paths)
    {
        foreach ($paths as $path) {
            $path = FilePath::normalize($path);
            if (! Str::startsWith($path, Container::getInstance()->basePath().DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
                yield $path;
            }
        }
    }

    /**
     * @param  array<string, string[]>  $pathsList
     * @return array<string, \Generator<int, string>>
     */
    private static function normalizeAndFilterVendorPaths(array $pathsList)
    {
        return Loop::map($pathsList, function ($paths) {
            return self::filterPaths($paths);
        });
    }
}
