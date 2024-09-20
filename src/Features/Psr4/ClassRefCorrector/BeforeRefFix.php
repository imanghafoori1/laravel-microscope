<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\ClassRefCorrector;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class BeforeRefFix
{

    public static function getCallback($command)
    {
        if ($command->option('force-ref-fix')) {
            return function () {
                return true;
            };
        }

        return function (PhpFileDescriptor $file, $lineIndex, $lineContent) use ($command) {
            $command->getOutput()->writeln(
                ErrorPrinter::getLink($file->getAbsolutePath(), $lineIndex)
            );

            $command->warn($lineContent);

            return $command->confirm(self::getQuestion(), true);
        };
    }

    private static function getQuestion(): string
    {
        return 'Do you want to update reference to the old namespace?';
    }
}
