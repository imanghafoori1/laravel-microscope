<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Throwable;

class ChecksOnPsr4Classes
{
    public static $exceptions = [];

    /**
     * @var int
     */
    public static $checkedFilesNum = 0;

    public static function apply($includeFile, $includeFolder, $params, $checks)
    {
        $stats = [];
        foreach (ComposerJson::readAutoload() as $composerPath => $psr4) {
            foreach ($psr4 as $psr4Namespace => $psr4Paths) {
                foreach ((array) $psr4Paths as $psr4Path) {
                    $filesCount = self::applyChecksInPath($psr4Path, $includeFile, $includeFolder, $params, $psr4Namespace, $checks);

                    self::$checkedFilesNum += $filesCount;
                    $stats[$composerPath][$psr4Namespace][$psr4Path] = $filesCount;
                }
            }
        }

        return $stats;
    }

    private static function getParams($params, array $tokens, $absFilePath, $psr4Path, $psr4Namespace)
    {
        return (! is_array($params) && is_callable($params)) ? $params($tokens, $absFilePath, $psr4Path, $psr4Namespace) : $params;
    }

    private static function applyChecksInPath($psr4Path, $includeFile, $includeFolder, $params, $psr4Namespace, $checks): int
    {
        $filesCount = 0;
        foreach (FilePath::getAllPhpFiles($psr4Path) as $phpFilePath) {
            $absFilePath = $phpFilePath->getRealPath();
            if (! FilePath::contains($absFilePath, $includeFile, $includeFolder)) {
                continue;
            }
            $filesCount++;
            $tokens = token_get_all(file_get_contents($absFilePath));

            $params1 = self::getParams($params, $tokens, $absFilePath, $psr4Path, $psr4Namespace);
            foreach ($checks as $check) {
                $newTokens = self::applyCheck($check, $tokens, $absFilePath, $params1, $phpFilePath, $psr4Path, $psr4Namespace);
                if ($newTokens) {
                    $tokens = $newTokens;
                    $params1 = self::getParams($params, $tokens, $absFilePath, $psr4Path, $psr4Namespace);
                }
            }
        }

        return $filesCount;
    }

    private static function applyCheck($check, $tokens, $absFilePath, $params1, $phpFilePath, $psr4Path, $psr4Namespace)
    {
        try {
            return $check::check($tokens, $absFilePath, $params1, $phpFilePath, $psr4Path, $psr4Namespace);
        } catch (Throwable $exception) {
            self::$exceptions[] = $exception;
        }
    }
}
