<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\ClassRefCorrector;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Console;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class BeforeRefFix
{
    /**
     * @return \Closure(): bool
     */
    public static function getCallback($forceRefFix)
    {
        if ($forceRefFix) {
            return fn () => true;
        }

        return function (PhpFileDescriptor $file, $lineIndex, $lineContent) {
            Console::getInstance()->writeln(
                ErrorPrinter::getLink($file->getAbsolutePath(), $lineIndex)
            );

            Console::getInstance()->writeln($lineContent);

            return Console::confirm(self::getQuestion());
        };
    }

    private static function getQuestion(): string
    {
        return 'Do you want to update reference to the old namespace?';
    }
}
