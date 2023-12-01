<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\Analyzers;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class ClassAtMethodHandler
{
    public static $fix = true;

    public static function handle($absFilePath, $atSignTokens)
    {
        $fix = false;

        foreach ($atSignTokens as $token) {
            $trimmed = \trim($token[1], '\'\"');
            [$class, $method] = \explode('@', $trimmed);

            $class = \str_replace('\\\\', '\\', $class);

            if (! \class_exists($class)) {
                $result = [false];

                if (self::$fix && Analyzers\Fixer::isInUserSpace($class)) {
                    $result = Analyzers\Fixer::fixReference($absFilePath, $class, $token[2]);
                }

                if ($result[0]) {
                    $fix = true;
                    self::printFixation($absFilePath, $class, $token[2], $result[1]);
                } else {
                    self::wrongUsedClassError($absFilePath, $token[1], $token[2]);
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

    private static function printFixation($absPath, $wrongClass, $lineNumber, $correct)
    {
        $header = $wrongClass.'  <=== Did not exist';
        $msg = 'Fixed to:  '.substr($correct[0], 0, 55);

        ErrorPrinter::singleton()->simplePendError($msg, $absPath, $lineNumber, 'ns_replacement', $header);
    }

    private static function wrongUsedClassError($absPath, $class, $lineNumber)
    {
        ErrorPrinter::singleton()->simplePendError($class, $absPath, $lineNumber, 'wrongUsedClassError', 'Class does not exist:');
    }
}
