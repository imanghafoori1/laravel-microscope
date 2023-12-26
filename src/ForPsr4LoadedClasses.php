<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Composer;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Iterators\ChecksOnPsr4Classes;

class ForPsr4LoadedClasses
{
    /**
     * @param  array<class-string<\Imanghafoori\LaravelMicroscope\Iterators\Check>>  $checks
     * @param  $params
     * @return \Traversable
     */
    public static function check($checks, $params = [], $includeFile = '', $includeFolder = '')
    {
        [$stats, $exceptions] = ChecksOnPsr4Classes::apply($checks, $params, $includeFile, $includeFolder);

        foreach ($exceptions as $e) {
            self::handleErrorException($e);
            self::handleClassNotFound($e);
        }

        return $stats;
    }

    public static function checkNow($checks, $params = [], $includeFile = '', $includeFolder = '')
    {
        foreach (self::check($checks, $params, $includeFile, $includeFolder) as $result) {
            iterator_to_array($result);
        }
    }

    private static function warnDumping($msg)
    {
        $p = ErrorPrinter::singleton()->printer;
        $p->writeln('It seems composer has some trouble with autoload...');
        $p->writeln($msg);
        $p->writeln('Running "composer dump-autoload" command...  \(*_*)\  ');
    }

    private static function entityNotFound(string $msg)
    {
        return self::startsWith($msg, ['Enum ', 'Interface ', 'Class ', 'Trait ']) && self::endsWith($msg, ' not found');
    }

    private static function composerWillNeedADumpAutoload($e)
    {
        $end = str_replace('|', DIRECTORY_SEPARATOR, 'vendor|composer|ClassLoader.php');

        return self::endsWith($e->getFile(), $end);
    }

    private static function startsWith($haystack, $needles)
    {
        foreach ($needles as $needle) {
            if (substr($haystack, 0, strlen($needle)) === $needle) {
                return true;
            }
        }

        return false;
    }

    private static function endsWith($haystack, $needle)
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }

    private static function handleErrorException($e)
    {
        // In case a file is moved or deleted, composer will need a dump autoload.
        if (self::composerWillNeedADumpAutoload($e)) {
            self::warnDumping($e->getMessage());
            resolve(Composer::class)->dumpAutoloads();
        }
    }

    private static function handleClassNotFound($e)
    {
        if (self::entityNotFound($e->getMessage())) {
        } else {
            ErrorPrinter::singleton()->simplePendError($e->getMessage(), $e->getFile(), $e->getLine(), 'error', get_class($e));
        }
    }
}
