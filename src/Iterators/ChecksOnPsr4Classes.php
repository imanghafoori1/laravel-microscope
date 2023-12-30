<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Throwable;

class ChecksOnPsr4Classes
{
    use FiltersFiles;

    /**
     * @var \Throwable[]
     */
    public static $exceptions = [];

    /**
     * @var int
     */
    public static $checkedFilesCount = 0;

    /**
     * @param  array<class-string<\Imanghafoori\LaravelMicroscope\Iterators\Check>>  $checks
     * @param  $params
     * @return array<string, \Generator>
     */
    public static function apply($checks, $params, $includeFile, $includeFolder)
    {
        $includeFile && FilePath::$fileName = $includeFile;
        $stats = [];
        foreach (ComposerJson::readAutoload() as $composerPath => $psr4) {
            $stats[$composerPath] = self::processGetStats($psr4, $checks, $params, $includeFolder);
        }

        try {
            return [$stats, self::$exceptions];
        } finally {
            self::$exceptions = [];
        }
    }

    private static function getParams($params, array $tokens, $absFilePath, $psr4Path, $psr4Namespace)
    {
        return (! is_array($params) && is_callable($params)) ? $params($tokens, $absFilePath, $psr4Path, $psr4Namespace) : $params;
    }

    /**
     * @param  array<class-string<\Imanghafoori\LaravelMicroscope\Iterators\Check>>  $checks
     * @param  string  $psr4Namespace
     * @param  string  $psr4Path
     * @param  array  $params
     * @return int
     */
    private static function applyChecksInPath($psr4Namespace, $psr4Path, $checks, $params, $includeFolder): int
    {
        $filesCount = 0;
        $finder = FilePath::getAllPhpFiles($psr4Path);
        $includeFolder && $finder = self::filterFiles($finder, $includeFolder);
        foreach ($finder as $phpFilePath) {
            $filesCount++;
            self::applyChecks($phpFilePath, $params, $psr4Path, $psr4Namespace, $checks);
        }

        return $filesCount;
    }

    private static function applyChecks($phpFilePath, $params, $psr4Path, $psr4Namespace, $checks)
    {
        $absFilePath = $phpFilePath->getRealPath();
        $tokens = token_get_all(file_get_contents($absFilePath));

        $processedParams = self::getParams($params, $tokens, $absFilePath, $psr4Path, $psr4Namespace);
        foreach ($checks as $check) {
            try {
                /**
                 * @var $check \Imanghafoori\LaravelMicroscope\Iterators\Check
                 */
                $newTokens = $check::check($tokens, $absFilePath, $processedParams, $phpFilePath, $psr4Path, $psr4Namespace);
                if ($newTokens) {
                    $tokens = $newTokens;
                    $processedParams = self::getParams($params, $tokens, $absFilePath, $psr4Path, $psr4Namespace);
                }
            } catch (Throwable $exception) {
                self::$exceptions[] = $exception;
            }
        }
    }

    /**
     * @param  $psr4
     * @param  array  $checks
     * @param  $params
     * @param  $includeFolder
     * @return \Generator
     */
    private static function processGetStats($psr4, array $checks, $params, $includeFolder)
    {
        foreach ($psr4 as $psr4Namespace => $psr4Paths) {
            yield $psr4Namespace => self::processPaths($psr4Namespace, $psr4Paths, $checks, $params, $includeFolder);
        }
    }

    private static function processPaths($psr4Namespace, $psr4Paths, $checks, $params, $includeFolder)
    {
        foreach ((array) $psr4Paths as $psr4Path) {
            $filesCount = self::applyChecksInPath($psr4Namespace, $psr4Path, $checks, $params, $includeFolder);
            self::$checkedFilesCount += $filesCount;

            yield $psr4Path => $filesCount;
        }
    }
}
