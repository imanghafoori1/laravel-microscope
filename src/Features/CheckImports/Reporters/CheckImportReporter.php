<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

class CheckImportReporter
{
    use Reporting;

    public static function totalImportsMsg($refCount)
    {
        return '<options=bold;fg=yellow>'.$refCount.' imports were checked under:</>';
    }

    public static function getRouteStats($count)
    {
        return self::blue($count).' route'.($count <= 1 ? '' : 's');
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
