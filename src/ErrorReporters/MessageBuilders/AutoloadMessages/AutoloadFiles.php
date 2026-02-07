<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\AutoloadMessages;

use Imanghafoori\LaravelMicroscope\ErrorReporters\Reporting;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use JetBrains\PhpStorm\Pure;

class AutoloadFiles
{
    use Reporting;

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\FilesDto  $filesList
     * @return string
     */
    public static function getLines($filesList)
    {
        $lines = self::formatFiles($filesList);
        $total = count($lines);

        return $total ? [self::autoloadFilesHeader($total), $lines] : [];
    }

    #[Pure]
    private static function autoloadFilesHeader(int $count): string
    {
        $s = ($count <= 1 ? '' : 's');

        return self::hyphen().' Autoloaded files'.' '.Color::white("($count file$s)");
    }
}
