<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

use JetBrains\PhpStorm\Pure;

class RouteReport
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\FilesDto  $routeFiles
     * @return string
     */
    #[Pure]
    public static function getStats($routeFiles)
    {
        $linesArr = CheckImportReporter::formatFiles($routeFiles);
        $count = count($linesArr);
        $lines = implode('', $linesArr);

        return CheckImportReporter::hyphen().'route'.($count <= 1 ? '' : 's').' <fg=white>('.$count.' files)</>'.$lines;
    }
}
