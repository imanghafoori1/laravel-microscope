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
     * @param $checker
     * @return array<string, \Generator>
     */
    public static function apply($checker)
    {
        self::$checker = $checker;

        $stats = self::processAll();

        self::handleExceptions();

        return $stats;
    }

    /**
     * @param  $psr4
     * @return \Generator
     */
    private static function processGetStats($psr4)
    {
        foreach ($psr4 as $psr4Namespace => $psr4Paths) {
            yield $psr4Namespace => self::processPaths($psr4Namespace, $psr4Paths);
        }
    }

    /**
     * @param  string  $psr4Namespace
     * @param  string[]|string  $psr4Paths
     * @return \Generator
     */
    private static function processPaths($psr4Namespace, $psr4Paths)
    {
        foreach ((array) $psr4Paths as $psr4Path) {
            $filesCount = (self::$checker)->applyChecksInPath($psr4Namespace, $psr4Path);
            self::$checkedFilesCount += $filesCount;

            yield $psr4Path => $filesCount;
        }
    }

    private static function handleExceptions()
    {
        foreach ((self::$checker)->exceptions as $e) {
            self::$errorExceptionHandler::handle($e);
        }
    }

    private static function processAll()
    {
        $stats = [];
        foreach (ComposerJson::readPsr4() as $composerPath => $psr4) {
            $stats[$composerPath] = self::processGetStats($psr4);
        }

        return $stats;
    }
}
