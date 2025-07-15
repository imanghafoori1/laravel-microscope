<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

use JetBrains\PhpStorm\Pure;

class AutoloadFiles
{
    use Reporting;

    /**
     * @param  \Generator  $filesListGen
     * @return string
     */
    public static function getLines($filesListGen)
    {
        $lines = '';
        $total = 0;
        foreach ($filesListGen as $files) {
            $linesArr = self::formatFiles($files);
            $total += count($linesArr);
            $lines .= implode('', $linesArr);
        }

        return $total ? self::autoloadFilesHeader($total, $lines) : '';
    }

    #[Pure]
    private static function autoloadFilesHeader(int $count, string $lines): string
    {
        return self::blue($count).' autoloaded file'.($count <= 1 ? '' : 's').$lines;
    }
}
