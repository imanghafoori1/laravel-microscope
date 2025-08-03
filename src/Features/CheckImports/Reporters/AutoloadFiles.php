<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

use JetBrains\PhpStorm\Pure;

class AutoloadFiles
{
    use Reporting;

    /**
     * @param  \Generator  $filesList
     * @return string
     */
    public static function getLines($filesList)
    {
        $lines = self::formatFiles($filesList);
        $total = count($lines);
        $lines = implode('', $lines);

        return $total ? self::autoloadFilesHeader($total, $lines) : '';
    }

    #[Pure]
    private static function autoloadFilesHeader(int $count, string $lines): string
    {
        return self::blue($count).' autoloaded file'.($count <= 1 ? '' : 's').$lines;
    }
}
