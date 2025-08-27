<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;

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
        foreach ($psr4 as $psr4Namespace => $psr4Paths) {
            $psr4[$psr4Namespace] = self::applyCheckOnFilesInPaths($psr4Namespace, $psr4Paths);
        }

        return $psr4;
    }

    /**
     * @param  string  $psr4Namespace
     * @param  string[]|string  $psr4Paths
     * @return array<string, (callable(): int)>
     */
    private static function applyCheckOnFilesInPaths($psr4Namespace, $psr4Paths)
    {
        $pathStats = [];
        foreach ((array) $psr4Paths as $psr4Path) {
            $checker = self::$checker;
            $filesCount = function () use ($psr4Namespace, $psr4Path, $checker) {
                return $checker->applyChecksInPath($psr4Namespace, $psr4Path);
            };

            $pathStats[$psr4Path] = $filesCount;
        }

        return $pathStats;
    }

    private static function handleExceptions()
    {
        foreach ((self::$checker)->exceptions as $e) {
            self::$errorExceptionHandler::handle($e);
        }
    }

    /**
     * @return array<string, array<string, array<string, (callable(): int)>>>
     */
    private static function processAll()
    {
        $stats = [];
        foreach (ComposerJson::readPsr4() as $composerPath => $psr4) {
            $stats[$composerPath] = self::processGetStats($psr4);
        }

        return $stats;
    }
}
