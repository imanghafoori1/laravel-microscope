<?php

namespace Imanghafoori\LaravelMicroscope\Handlers;

use Imanghafoori\LaravelMicroscope\Analyzers\ImportsAnalyzer;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class UnusedWrongImports
{
    public static function handle($unusedWrongImports, $absFilePath)
    {
        $printer = app(ErrorPrinter::class);

        foreach ($unusedWrongImports as $class) {
            ImportsAnalyzer::$wrongImportsCount++;
            $printer->wrongImport($absFilePath, $class[0], $class[1]);
        }
    }
}
