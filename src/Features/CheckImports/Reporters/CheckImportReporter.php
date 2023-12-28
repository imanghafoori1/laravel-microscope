<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

class CheckImportReporter
{
    use Reporting;

    public static function totalImportsMsg()
    {
        return '<options=bold;fg=yellow>Imports were checked under:</>';
    }

    public static function getRouteStats($basePath, $routeFiles)
    {
        $lines = '';

        $count = 0;
        foreach ($routeFiles as $routePath) {
            $count++;
            $relPath = str_replace($basePath, '', $routePath);
            $relPath = ltrim($relPath, DIRECTORY_SEPARATOR);
            $relPath = str_replace(DIRECTORY_SEPARATOR, '/', $relPath);
            $lines .= PHP_EOL.'    '.self::hyphen('<fg=green>'.ltrim($relPath, DIRECTORY_SEPARATOR).'</>');
        }

        return self::blue($count).' route'.($count <= 1 ? '' : 's').$lines;
    }

    public static function getFilesStats($count)
    {
        return self::blue($count).'class'.($count <= 1 ? '' : 'es');
    }

    public static function header(): string
    {
        return ' ⬛️ <fg=blue>Overall:</>';
    }
}
