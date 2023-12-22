<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Generator;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Throwable;

class ChecksOnPsr4Classes
{
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
     * @param  $includeFile
     * @param  $includeFolder
     * @return array
     */
    public static function apply($checks, $params, $includeFile, $includeFolder)
    {
        $stats = [];
        foreach (ComposerJson::readAutoload() as $composerPath => $psr4) {
            foreach ($psr4 as $psr4Namespace => $psr4Paths) {
                foreach ((array) $psr4Paths as $psr4Path) {
                    $filesCount = self::applyChecksInPath($checks, $psr4Namespace, $psr4Path, $includeFile, $includeFolder, $params);

                    self::$checkedFilesCount += $filesCount;
                    $stats[$composerPath][$psr4Namespace][$psr4Path] = $filesCount;
                }
            }
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
     * @param  string  $includeFile
     * @param  string  $includeFolder
     * @param  array  $params
     * @return int
     */
    private static function applyChecksInPath($checks, $psr4Namespace, $psr4Path, $includeFile, $includeFolder, $params): int
    {
        $filesCount = 0;
        foreach (self::filterPaths(FilePath::getAllPhpFiles($psr4Path), $includeFile, $includeFolder) as $phpFilePath) {
            $filesCount++;
            self::applyChecks($phpFilePath, $params, $psr4Path, $psr4Namespace, $checks);
        }

        return $filesCount;
    }

    private static function filterPaths($phpFilePaths, $includeFile, $includeFolder): Generator
    {
        foreach ($phpFilePaths as $phpFilePath) {
            if (FilePath::contains($phpFilePath->getRealPath(), $includeFile, $includeFolder)) {
                yield $phpFilePath;
            }
        }
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
}
