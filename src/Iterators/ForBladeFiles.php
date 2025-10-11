<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Iterators\DTO\BladeStatDto;

class ForBladeFiles implements Check
{
    public static $paths;

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\CheckSet  $checkSet
     * @return array<string, BladeStatDto>
     */
    public static function check($checkSet)
    {
        self::withoutComponentTags();
        $mapper = fn ($paths) => BladeStatDto::make(
            BladeFiles\CheckBladePaths::checkPaths($paths, $checkSet)
        );

        return Loop::map(self::getViewsPaths(), $mapper);
    }

    /**
     * @return array<string, \Generator<int, string>>
     */
    public static function getViewsPaths()
    {
        if (self::$paths) {
            $hints = self::$paths;
        } else {
            $hints = View::getFinder()->getHints();
            $hints['random_key_69471'] = View::getFinder()->getPaths();
            unset(
                $hints['notifications'],
                $hints['pagination']
            );
        }

        return self::normalizeAndFilterVendorPaths($hints);
    }

    /**
     * @return void
     */
    private static function withoutComponentTags()
    {
        try {
            $compiler = app('microscope.blade.compiler');
            method_exists($compiler, 'withoutComponentTags') && $compiler->withoutComponentTags();
        } catch (Exception $e) {
            //
        }
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
        return Loop::map($pathsList, fn ($paths) => self::filterPaths($paths));
    }
}
