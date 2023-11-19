<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Analyzers;

class ClassAtMethod
{
    public static function handle($absFilePath, $atSignTokens)
    {
        $fix = false;
        /**
         * @var ErrorPrinter $printer
         */
        $printer = ErrorPrinter::singleton();

        foreach ($atSignTokens as $token) {
            $trimmed = \trim($token[1], '\'\"');
            [$class, $method] = \explode('@', $trimmed);

            $class = str_replace('\\\\', '\\', $class);

            if (! \class_exists($class)) {
                $isInUserSpace = Analyzers\Fixer::isInUserSpace($class);

                $result = [false];
                if ($isInUserSpace) {
                    $result = Analyzers\Fixer::fixReference($absFilePath, $class, $token[2]);
                }

                if ($result[0]) {
                    $fix = true;
                    $printer->printFixation($absFilePath, $class, $token[2], $result[1]);
                } else {
                    $printer->wrongUsedClassError($absFilePath, $token[1], $token[2]);
                }
            } elseif (! \method_exists($class, $method)) {
                self::wrongMethodError($absFilePath, $trimmed, $token[2]);
            }
        }

        return $fix;
    }

    private static function wrongMethodError($absPath, $class, $lineNumber)
    {
        ErrorPrinter::singleton()->simplePendError(
            $class, $absPath, $lineNumber, 'wrongMethodError', 'Method does not exist:'
        );
    }
}
