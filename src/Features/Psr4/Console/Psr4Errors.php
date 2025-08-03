<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\Console;

use Illuminate\Console\Command;
use ImanGhafoori\ComposerJson\NamespaceErrors\NamespaceError;
use Imanghafoori\LaravelMicroscope\Features\Psr4\ClassRefCorrector\ClassRefCorrector;
use Imanghafoori\LaravelMicroscope\Features\Psr4\ClassRefCorrector\FilePathsForReferenceFix;
use Imanghafoori\LaravelMicroscope\Features\Psr4\Console\NamespaceFixer\NamespaceFixerMessages;
use Imanghafoori\LaravelMicroscope\Features\Psr4\NamespaceFixer;
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
        if ($error->errorType() === 'namespace') {
            self::askAndFixNamespace($error);
        } elseif ($error->errorType() === 'filename') {
            self::wrongFileName($error->entity);
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
        $answer = self::getAnswer($file, $from, $class, $to);

        if ($answer) {
            NamespaceFixer::fix($file, $from, $to);
            self::$command->getOutput()->writeln('Namespace updated to: '.$to);
            self::$command->getOutput()->writeln('Searching for old references...');
            self::updateOldRefs($from, $to, $class);
            self::deleteLine(2);
            NamespaceFixerMessages::fixedNamespace($file, $from, $to, $class);
        } else {
            NamespaceFixerMessages::wrongNamespace($file, $from, $to, $class);
        }
    }

    private static function askAndFixNamespace(NamespaceError $error)
    {
        self::applyFixProcess(
            PhpFileDescriptor::make($error->entity->getAbsolutePath()),
            $error->entity->getNamespace(),
            $error->entity->getEntityName(),
            $error->getShortest()
        );
    }

    private static function wrongFileName($error)
    {
        NamespaceFixerMessages::wrongFileName(
            $error['relativePath'],
            $error['class'],
            $error['fileName']
        );
    }

    public static function deleteLine($lines = 1): void
    {
        $output = self::$command->getOutput();
        $i = 0;
        while (true) {
            $output->write("\x1b[1A\x1b[1G\x1b[2K");
            $i++;
            if ($i >= $lines) {
                break;
            }
            usleep(70000);
        }
    }

    private static function getAnswer(PhpFileDescriptor $file, $from, $class, $to): mixed
    {
        if (self::$command->option('nofix')) {
            $answer = false;
        } elseif (self::$command->option('force')) {
            $answer = true;
        } else {
            NamespaceFixerMessages::warnIncorrectNamespace($file->relativePath(), $from, $class);
            $answer = self::$confirm::ask(self::$command, $to);
            self::deleteLine(9);
        }

        return $answer;
    }
}
