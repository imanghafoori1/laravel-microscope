<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\ClassRefCorrector;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Console;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class BeforeRefFix
{
    /**
     * @param  $command
     * @return \Closure(): bool
     */
    public static function getCallback($command)
    {
        if ($command->option('force-ref-fix')) {
            return fn () => true;
        }

        return function (PhpFileDescriptor $file, $lineIndex, $lineContent) use ($command) {
            $command->getOutput()->writeln(
                ErrorPrinter::getLink($file->getAbsolutePath(), $lineIndex)
            );

            $command->warn($lineContent);

            return Console::confirm(self::getQuestion());
        };
    }

    private static function getQuestion(): string
    {
        return 'Do you want to update reference to the old namespace?';
    }
}
