<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\PhpFinder;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Throwable;

class ChecksOnPsr4Classes
{
    use FiltersFiles;

    /**
     * @var class-string
     */
    public static $errorExceptionHandler;

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
     * @param  array  $params
     * @param  string  $includeFile
     * @param  string  $includeFolder
     * @return array<string, \Generator>
     */
    public static function apply($checks, $params, $includeFile, $includeFolder)
    {
        $includeFile && PhpFinder::$fileName = $includeFile;

        $stats = self::processAll($checks, $params, $includeFolder);

        self::handleExceptions();

        return $stats;
    }

    private static function getParams($params, PhpFileDescriptor $file, $psr4Path, $psr4Namespace)
    {
        return (! is_array($params) && is_callable($params)) ? $params($file, $psr4Path, $psr4Namespace) : $params;
    }

    /**
     * @param  string  $psr4Namespace
     * @param  string  $psr4Path
     * @param  array<class-string<\Imanghafoori\LaravelMicroscope\Iterators\Check>>  $checks
     * @param  array  $params
     * @param  string  $includeFolder
     * @return int
     */
    private static function applyChecksInPath($psr4Namespace, $psr4Path, $checks, $params, $includeFolder): int
    {
        $filesCount = 0;
        $finder = PhpFinder::getAllPhpFiles($psr4Path);
        $includeFolder && $finder = self::filterFiles($finder, $includeFolder);
        foreach ($finder as $phpFilePath) {
            $filesCount++;
            self::applyChecks($phpFilePath, $params, $psr4Path, $psr4Namespace, $checks);
        }

        return $filesCount;
    }

    private static function applyChecks($phpFileObj, $params, $psr4Path, $psr4Namespace, $checks)
    {
        $absFilePath = $phpFileObj->getRealPath();

        $file = PhpFileDescriptor::make($absFilePath);

        $processedParams = self::getParams($params, $file, $psr4Path, $psr4Namespace);
        foreach ($checks as $check) {
            try {
                /**
                 * @var $check \Imanghafoori\LaravelMicroscope\Iterators\Check
                 */
                $newTokens = $check::check($file, $processedParams, $psr4Path, $psr4Namespace);
                if ($newTokens) {
                    $file->setTokens($newTokens);
                    $processedParams = self::getParams($params, $file, $psr4Path, $psr4Namespace);
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

    private static function handleExceptions()
    {
        foreach (self::$exceptions as $e) {
            self::$errorExceptionHandler::handle($e);
        }

        self::$exceptions = [];
    }

    private static function processAll(array $checks, $params, $includeFolder)
    {
        $stats = [];
        foreach (ComposerJson::readPsr4() as $composerPath => $psr4) {
            $stats[$composerPath] = self::processGetStats($psr4, $checks, $params, $includeFolder);
        }

        return $stats;
    }
}
