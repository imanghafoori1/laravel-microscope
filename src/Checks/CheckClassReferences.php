<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class CheckClassReferences
{
    public static $refCount = 0;

    public static function check($tokens, $absPath)
    {
        [$classes,] = ParseUseStatement::findClassReferences($tokens);

        $printer = app(ErrorPrinter::class);
        foreach ($classes as $class) {
            self::$refCount++;
            if (! self::exists($class['class'])) {
                $printer->wrongUsedClassError($absPath, $class['class'], $class['line']);
            }
        }
    }

    private static function exists($class)
    {
        try {
            return class_exists($class) || interface_exists($class) || function_exists($class);
        } catch (\Error $e) {
            app(ErrorPrinter::class)->simplePendError($e->getMessage(), $e->getFile(), $e->getLine(), 'error', 'File error');

            return true;
        }
    }
}
