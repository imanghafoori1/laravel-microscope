<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;

class ForBladeFiles implements Check
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Check[]  $checkers
     * @param  $params
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return array<int, \Generator<string, int>>
     */
    public static function check($checkers, $params = [], $pathDTO = null)
    {
        self::withoutComponentTags();

        foreach (self::getViewsPaths() as $paths) {
            yield from BladeFiles\CheckBladePaths::checkPaths($paths, $checkers, $params, $pathDTO);
        }
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
     * @param $paths
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
     * @param array<string, array> $hints
     * @return array<string, \Generator<int, string>>
     */
    private static function normalizeAndFilterVendorPaths(array $hints)
    {
        $results = [];
        foreach ($hints as $key => $paths) {
            $paths = self::filterPaths($paths);
            if (empty($paths)) {
                continue;
            }
            $results[$key] = $paths;
        }

        return $results;
    }
}
