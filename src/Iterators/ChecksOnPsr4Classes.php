<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Iterators\DTO\Psr4StatsDTO;

class ChecksOnPsr4Classes
{
    /**
     * @var class-string
     */
    public static $errorExceptionHandler;

    /**
     * @var positive-int
     */
    public static $checkedFilesCount = 0;

    /**
     * @var \Imanghafoori\LaravelMicroscope\Iterators\CheckSet
     */
    private static $check;

    /**
     * @param  CheckSet  $check
     * @return array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\Psr4StatsDTO>
     */
    public static function apply($check)
    {
        self::$check = $check;

        $stats = self::processAll();

        self::handleExceptions();

        return $stats;
    }

    /**
     * @return array<string, \Imanghafoori\LaravelMicroscope\Iterators\DTO\Psr4StatsDTO>
     */
    private static function processAll()
    {
        return Loop::map(ComposerJson::readPsr4(), fn ($psr4) => self::processGetStats($psr4));
    }

    /**
     * @param  array<string, string|string[]>  $psr4
     * @return \Imanghafoori\LaravelMicroscope\Iterators\DTO\Psr4StatsDTO
     */
    private static function processGetStats($psr4)
    {
        return Psr4StatsDTO::make(
            Loop::map(
                $psr4,
                fn ($paths, $namespace) => self::checkFiles($namespace, $paths)
            )
        );
    }

    /**
     * @param  string  $psr4Namespace
     * @param  string[]|string  $psr4Paths
     * @return array<string, (callable(): int)>
     */
    private static function checkFiles($psr4Namespace, $psr4Paths)
    {
        return Loop::mapKey(
            (array) $psr4Paths,
            fn ($psr4Path) => [$psr4Path => self::getCounter($psr4Namespace, $psr4Path)]
        );
    }

    /**
     * @param  string  $psr4Namespace
     * @param  string  $psr4Path
     * @return \Closure(): int
     */
    private static function getCounter($psr4Namespace, $psr4Path)
    {
        return fn () => self::$check->applyChecksInPath($psr4Namespace, $psr4Path);
    }

    private static function handleExceptions()
    {
        Loop::map(
            self::$check->exceptions,
            fn ($e) => self::$errorExceptionHandler::handle($e)
        );
    }
}
