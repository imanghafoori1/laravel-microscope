<?php

namespace Imanghafoori\LaravelMicroscope\Handlers;

use Imanghafoori\LaravelMicroscope\Analyzers\ImportsAnalyzer;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

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
