<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Imanghafoori\LaravelMicroscope\Analyzers\ParseUseStatement;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckClassReferences
{
    public static $refCount = 0;

    public static function check($tokens, $absPath)
    {
        $classes = ParseUseStatement::findClassReferences($tokens, $absPath);

        foreach ($classes as $class) {
            self::$refCount++;
            if (! self::exists($class['class'])) {
                app(ErrorPrinter::class)->wrongUsedClassError($absPath, $class['class'], $class['line']);
            }
        }
    }

    private static function exists($class)
    {
        return class_exists($class) || interface_exists($class);
    }
}
