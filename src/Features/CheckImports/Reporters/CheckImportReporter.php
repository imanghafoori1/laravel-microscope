<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

class CheckImportReporter
{
    use Reporting;

    public static function totalImportsMsg($refCount)
    {
        return '<options=bold;fg=yellow>'.$refCount.' imports were checked under:</>';
    }

    public static function getRouteStats($value)
    {
        $value = array_values($value)[0];

        return self::blue($value).' route'.($value <= 1 ? '' : 's');
    }

    use Reporting;

    public static function getClassMapStats($stat)
    {
        $output = '';
        $total = array_sum($stat);
        $output .= self::blue($total).' classmap:';
        foreach ($stat as $key => $count) {
            $output .= self::addLine($key, $count);
        }

        return $output;
    }

    public static function getAutoloadedFiles($count)
    {
        $count = array_values($count)[0];

        return self::blue($count).' autoloaded file'.($count <= 1 ? '' : 's');
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
