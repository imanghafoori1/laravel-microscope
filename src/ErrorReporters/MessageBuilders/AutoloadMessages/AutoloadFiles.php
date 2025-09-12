<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\AutoloadMessages;

use Imanghafoori\LaravelMicroscope\ErrorReporters\Reporting;
use JetBrains\PhpStorm\Pure;

class AutoloadFiles
{
    use Reporting;

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\FilesDto  $filesList
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
