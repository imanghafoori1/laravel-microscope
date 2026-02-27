<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\Console;

use ImanGhafoori\ComposerJson\NamespaceErrors\NamespaceError;
use Imanghafoori\LaravelMicroscope\Features\Psr4\ClassRefCorrector\BeforeRefFix;
use Imanghafoori\LaravelMicroscope\Features\Psr4\ClassRefCorrector\ClassRefCorrector;
use Imanghafoori\LaravelMicroscope\Features\Psr4\ClassRefCorrector\FilePathsForReferenceFix;
use Imanghafoori\LaravelMicroscope\Features\Psr4\Console\NamespaceFixer\NamespaceFixerMessages;
use Imanghafoori\LaravelMicroscope\Features\Psr4\NamespaceFixer;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\Console;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class AskAndFixNamespace
{
    /**
     * @var \Imanghafoori\LaravelMicroscope\Features\Psr4\Console\Options
     */
    public static $options;

    public static $refCorrector = ClassRefCorrector::class;

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
        $answer = Ask::getAnswer($file, $from, $class, $to, self::$options);

        if ($answer) {
            self::performFix($file, $from, $to, $class);
        } else {
            NamespaceFixerMessages::wrongNamespace($file, $from, $to, $class);
        }
    }

    private static function performFix(PhpFileDescriptor $file, $from, $to, $class): void
    {
        NamespaceFixer::fix($file, $from, $to);
        $output = Console::getInstance();
        $output->writeln('Namespace updated to: '.Color::blue($to));
        $output->writeln('Searching for old references...');
        if ($from && ! self::$options->noRefFix) {
            self::updateOldRefs($from, $to, $class);
        }
        Console::deleteLine(2);
        NamespaceFixerMessages::fixedNamespace($file, $from, $to, $class);
    }

    private static function updateOldRefs($from, $to, $class)
    {
        $before = BeforeRefFix::getCallback(self::$options->forceRefFix);

        self::$refCorrector::fixOldRefs(
            $from, $class, $to, FilePathsForReferenceFix::getFiles(), $before
        );
    }
}
