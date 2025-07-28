<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;

class BladeFiles implements Check
{
    /**
     * @param  $checkers
     * @param  $params
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return \Generator<string, int>
     */
    public static function check($checkers, $params = [], $pathDTO = null)
    {
        self::withoutComponentTags();

        foreach (self::getViews() as $paths) {
            yield from BladeFiles\CheckBladePaths::checkPaths($paths, $checkers, $pathDTO, $params);
        }
    }

    /**
     * @return \Generator<string, array>
     */
    public static function getViews()
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

    private static function filterPaths($paths): array
    {
        $newPaths = [];
        foreach ($paths as $path) {
            $path = FilePath::normalize($path);
            if (! Str::startsWith($path, Container::getInstance()->basePath().DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
                $newPaths[] = $path;
            }
        }

        return $newPaths;
    }

    private static function normalizeAndFilterVendorPaths(array $hints)
    {
        foreach ($hints as $key => $paths) {
            $paths = self::filterPaths($paths);
            if (empty($paths)) {
                continue;
            }
            yield $key => $paths;
        }
    }
}
