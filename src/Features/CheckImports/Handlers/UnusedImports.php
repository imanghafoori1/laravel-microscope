<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\ImportsAnalyzer;

class UnusedImports
{
    public static function handle($unusedCorrectImports, $absFilePath)
    {
        $printer = app(ErrorPrinter::class);

        foreach ($unusedCorrectImports as $class) {
            ImportsAnalyzer::$refCount++;
            ImportsAnalyzer::$unusedImportsCount++;
            $printer->extraImport($absFilePath, $class[0], $class[1]);
        }
    }
}
