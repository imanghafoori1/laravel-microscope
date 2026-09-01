<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Iterators;

use Exception;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\BladeFiles\CheckBladePaths;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\BladeStatDto;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;

class ForBladeFiles implements Check
{
    /**
     * @var array<string, string[]>
     */
    public static $paths;

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\CheckSet  $checkSet
     * @return array<string, BladeStatDto>
     */
    public static function check($checkSet)
    {
        self::withoutComponentTags();
        $mapper = static fn ($paths) => BladeStatDto::make(CheckBladePaths::checkPaths($paths, $checkSet));

        return Loop::map(self::getViewsPaths(), $mapper);
    }

    /**
     * @return array<string, \Generator<int, string>>
     */
    public static function getViewsPaths()
    {
        // normalize and filter vendor paths:
        return Loop::map(
            self::$paths,
            static fn ($paths) => self::filterPaths($paths)
        );
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
        $ds = DIRECTORY_SEPARATOR;
        foreach ($paths as $path) {
            $path = FilePath::normalize($path);
            if (! Str::startsWith($path, BasePath::$path.$ds.'vendor'.$ds)) {
                yield $path;
            }
        }
    }
}
