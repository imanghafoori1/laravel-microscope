<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\ClassRefCorrector;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class AfterRefFix
{
    public static function getCallback()
    {
        return function (PhpFileDescriptor $file, $changedLineNums, $content) {
            $file->putContents($content);
            $path = $file->getAbsolutePath();

            $printer = ErrorPrinter::singleton();
            foreach ($changedLineNums as $line) {
                $printer->simplePendError(
                    '', $path, $line, 'ns_replacement', 'Namespace replacement:'
                );
            }
        };
    }
}
