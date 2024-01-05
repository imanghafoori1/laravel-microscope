<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

use JetBrains\PhpStorm\Pure;

class CheckImportReporter
{
    use Reporting;

    public static function totalImportsMsg()
    {
        return '<options=bold;fg=yellow>Imports were checked under:</>';
    }

    public static function getRouteStats($basePath, $routeFiles)
    {
        $linesArr = self::formatFiles($routeFiles, $basePath);
        $count = count($linesArr);
        $lines = implode('', $linesArr);

        return self::blue($count).' route'.($count <= 1 ? '' : 's').$lines;
    }

    #[Pure]
    public static function getFilesStats($count)
    {
        return self::blue($count).'class'.($count <= 1 ? '' : 'es');
    }

    #[Pure]
    public static function header(): string
    {
        return ' ⬛️ <fg=blue>Overall:</>';
    }
}
