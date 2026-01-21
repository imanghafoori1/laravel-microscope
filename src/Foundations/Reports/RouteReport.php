<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Reports;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use JetBrains\PhpStorm\Pure;

class RouteReport
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\FilesDto  $routeFiles
     * @return string
     */
    #[Pure]
    public static function getStats($routeFiles)
    {
        $linesArr = CheckImportReporter::formatFiles($routeFiles);
        $count = count($linesArr);
        $lines = implode('', $linesArr);
        $count = Color::white('('.$count.' files)');

        return CheckImportReporter::hyphen().'route'.($count <= 1 ? '' : 's').' '.$count.$lines;
    }
}
