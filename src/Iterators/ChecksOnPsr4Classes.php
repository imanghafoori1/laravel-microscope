<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;

class ChecksOnPsr4Classes
{
    /**
     * @var class-string
     */
    public static $errorExceptionHandler;

    /**
     * @var int
     */
    public static $checkedFilesCount = 0;

    /**
     * @var \Imanghafoori\LaravelMicroscope\Iterators\CheckSingleMapping
     */
    private static $checker;

    /**
     * @param  CheckSingleMapping  $checker
     * @return array<string, array<string, array<string, (callable(): int)>>>
     */
    public static function apply($checker)
    {
        self::$checker = $checker;

        $stats = self::processAll();

        self::handleExceptions();

        return $stats;
    }

    /**
     * @param  array<string, string|string[]>  $psr4
     * @return array<string, array<string, (callable(): int)>>
     */
    private static function processGetStats($psr4)
    {
        $cb = fn ($paths, $namespace) => self::applyCheckOnFiles($namespace, $paths);

        return Loop::map($psr4, $cb);
    }

    /**
     * @param  string  $psr4Namespace
     * @param  string[]|string  $psr4Paths
     * @return array<string, (callable(): int)>
     */
    private static function applyCheckOnFiles($psr4Namespace, $psr4Paths)
    {
        $statsGenerator = [];
        foreach ((array) $psr4Paths as $psr4Path) {
            $statsGenerator[$psr4Path] = self::getCounter($psr4Namespace, $psr4Path);
        }

        return $statsGenerator;
    }

    private static function handleExceptions()
    {
        Loop::map(
            (self::$checker)->exceptions,
            fn ($e) => self::$errorExceptionHandler::handle($e)
        );
    }

    /**
     * @return array<string, array<string, array<string, (callable(): int)>>>
     */
    private static function processAll()
    {
        return Loop::map(ComposerJson::readPsr4(), fn ($psr4) => self::processGetStats($psr4));
    }

    /**
     * @param  string  $psr4Namespace
     * @param  string  $psr4Path
     * @return \Closure(): int
     */
    private static function getCounter($psr4Namespace, $psr4Path)
    {
        return fn () => (self::$checker)->applyChecksInPath($psr4Namespace, $psr4Path);
    }
}
