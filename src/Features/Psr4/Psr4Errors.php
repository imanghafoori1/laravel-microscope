<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class Psr4Errors
{
    /**
     * @var Command
     */
    private static $command;

    public static $refCorrector = ClassRefCorrector::class;

    public static $confirm = Confirm::class;

    public static function handle(array $errorsLists, $command)
    {
        self::$command = $command;

        foreach ($errorsLists as $errors) {
            foreach ($errors as $error) {
                self::handleError($error);
            }
        }
    }

    private static function handleError($error)
    {
        if ($error['type'] === 'namespace') {
            self::askAndFixNamespace($error);
        } elseif ($error['type'] === 'filename') {
            self::wrongFileName($error);
        }
    }

    private static function updateOldRefs($from, $to, $class)
    {
        if ($from && ! self::$command->option('no-ref-fix')) {
            self::$refCorrector::fixOldRefs($from, $class, $to, FilePathsForReferenceFix::getFiles());
        }
    }

    private static function applyFixProcess(PhpFileDescriptor $file, $from, $class, $to)
    {
        CheckPsr4Printer::warnIncorrectNamespace($file->relativePath(), $from, $class);

        if (self::$confirm::ask(self::$command, $to)) {
            NamespaceFixer::fix($file, $from, $to);
            self::updateOldRefs($from, $to, $class);
            CheckPsr4Printer::fixedNamespace($file, $from, $to, $class);
        }
    }

    private static function askAndFixNamespace($error)
    {
        self::applyFixProcess(
            PhpFileDescriptor::make($error['absFilePath']),
            $error['currentNamespace'],
            $error['class'],
            $error['correctNamespace']
        );
    }

    private static function wrongFileName($error)
    {
        CheckPsr4Printer::wrongFileName(
            $error['relativePath'],
            $error['class'],
            $error['fileName']
        );
    }
}
