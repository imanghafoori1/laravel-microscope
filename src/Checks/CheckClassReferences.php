<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Imanghafoori\LaravelMicroscope\Analyzers\ParseUseStatement;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\LaravelPaths\FilePath;

class CheckClassReferences
{
    public static $refCount = 0;

    public static function check($tokens, $absPath)
    {
        [$classes, $_] = ParseUseStatement::findClassReferences($tokens, $absPath);

        foreach ($classes as $class) {
            self::$refCount++;
            if (! self::exists($class['class'])) {
                app(ErrorPrinter::class)->wrongUsedClassError($absPath, $class['class'], $class['line']);
            }
        }
    }

    private static function exists($class)
    {
        try {
            return class_exists($class) || interface_exists($class);
        } catch (\Error $e) {
            $path = FilePath::getRelativePath($e->getFile());

            app(ErrorPrinter::class)->simplePendError($path, $e->getMessage(), $e->getLine(), 'error', 'File error');

            return true;
        }
    }
}
