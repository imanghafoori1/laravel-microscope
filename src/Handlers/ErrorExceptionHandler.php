<?php

namespace Imanghafoori\LaravelMicroscope\Handlers;

use Illuminate\Support\Composer;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class ErrorExceptionHandler
{
    public static function handle($e)
    {
        self::handleErrorException($e);
        self::handleClassNotFound($e);
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
        if (! self::entityNotFound($e->getMessage())) {
            ErrorPrinter::singleton()->simplePendError($e->getMessage(), $e->getFile(), $e->getLine(), 'error', get_class($e));
        } else {
            // ignore silently...
        }
    }
}
