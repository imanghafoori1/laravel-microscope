<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Analyzers\Fixer;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class ClassAtMethodHandler
{
    public static $fix = true;

    public static function handle(PhpFileDescriptor $file, $atSignTokens)
    {
        $fix = false;

        foreach ($atSignTokens as $token) {
            $trimmed = trim($token[1], '\'\"');
            [$class, $method] = explode('@', $trimmed);

            $class = str_replace('\\\\', '\\', $class);

            if (! class_exists($class)) {
                $result = [false];

                if (self::$fix && Fixer::isInUserSpace($class)) {
                    $result = Fixer::fixReference($file, $class, $token[2]);
                }

                if ($result[0]) {
                    $fix = true;
                    self::printFixation($file, $class, $token[2], $result[1]);
                } else {
                    self::wrongUsedClassError($file, $token[1], $token[2]);
                }
            } elseif (! method_exists($class, $method)) {
                self::wrongMethodError($file, $trimmed, $token[2], $method);
            }
        }

        return $fix;
    }

    private static function wrongMethodError(PhpFileDescriptor $file, $class, $lineNumber, $method)
    {
        ErrorPrinter::singleton()->simplePendError(
            $class, $file, $lineNumber, 'wrongMethodError', "Method '$method' does not exist:"
        );
    }

    private static function printFixation(PhpFileDescriptor $file, $wrongClass, $lineNumber, $correct)
    {
        $header = "$wrongClass  <=== Did not exist";
        $msg = 'Fixed to:  '.substr($correct[0], 0, 55);

        ErrorPrinter::singleton()->simplePendError(
            $msg, $file, $lineNumber, 'ns_replacement', $header
        );
    }

    private static function wrongUsedClassError(PhpFileDescriptor $file, $class, $lineNumber)
    {
        [$className] = explode('@', $class);

        ErrorPrinter::singleton()->simplePendError(
            $class, $file, $lineNumber, 'wrongUsedClassError', 'Class '.Color::blue(class_basename($className)).' does not exist:'
        );
    }
}
