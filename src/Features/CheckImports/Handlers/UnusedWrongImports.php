<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\ImportsAnalyzer;

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
