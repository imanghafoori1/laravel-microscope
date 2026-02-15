<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\Console;

use ImanGhafoori\ComposerJson\NamespaceErrors\NamespaceError;
use Imanghafoori\LaravelMicroscope\Features\Psr4\ClassRefCorrector\BeforeRefFix;
use Imanghafoori\LaravelMicroscope\Features\Psr4\ClassRefCorrector\ClassRefCorrector;
use Imanghafoori\LaravelMicroscope\Features\Psr4\ClassRefCorrector\FilePathsForReferenceFix;
use Imanghafoori\LaravelMicroscope\Features\Psr4\Console\NamespaceFixer\NamespaceFixerMessages;
use Imanghafoori\LaravelMicroscope\Features\Psr4\NamespaceFixer;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class AskAndFixNamespace
{
    public static $command;

    public static $refCorrector = ClassRefCorrector::class;

    public static $confirm = Confirm::class;

    public static $pause = 70_000;


    public static function handle(NamespaceError $error)
    {
        self::applyFixProcess(
            PhpFileDescriptor::make($error->entity->getAbsolutePath()),
            $error->entity->getNamespace(),
            $error->entity->getEntityName(),
            $error->getShortest()
        );
    }

    private static function applyFixProcess(PhpFileDescriptor $file, $from, $class, $to)
    {
        $answer = self::getAnswer($file, $from, $class, $to);

        if ($answer) {
            NamespaceFixer::fix($file, $from, $to);
            self::$command->getOutput()->writeln('Namespace updated to: '.Color::blue($to));
            self::$command->getOutput()->writeln('Searching for old references...');
            self::updateOldRefs($from, $to, $class);
            self::deleteLine(2);
            NamespaceFixerMessages::fixedNamespace($file, $from, $to, $class);
        } else {
            NamespaceFixerMessages::wrongNamespace($file, $from, $to, $class);
        }
    }

    private static function updateOldRefs($from, $to, $class)
    {
        if ($from && ! self::$command->option('no-ref-fix')) {
            $before = BeforeRefFix::getCallback(self::$command);

            self::$refCorrector::fixOldRefs(
                $from, $class, $to, FilePathsForReferenceFix::getFiles(), $before
            );
        }
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
            self::$pause && usleep(self::$pause);
        }
    }

    private static function getAnswer(PhpFileDescriptor $file, $from, $class, $to)
    {
        if (self::$command->option('nofix')) {
            $answer = false;
        } elseif (self::$command->option('force')) {
            $answer = true;
        } else {
            NamespaceFixerMessages::warnIncorrectNamespace($file, $from, $class);
            $answer = self::$confirm::ask(self::$command, $to);
            self::deleteLine(9);
        }

        return $answer;
    }
}