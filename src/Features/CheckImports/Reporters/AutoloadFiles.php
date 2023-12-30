<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

class AutoloadFiles
{
    use Reporting;

    /**
     * @param  string  $basePath
     * @param  \Generator  $filesListGen
     * @return string
     */
    public static function getLines($basePath, $filesListGen)
    {
        $lines = '';
        $total = 0;
        foreach ($filesListGen as $files) {
            $linesArr = self::formatFiles($files, $basePath);
            $total += count($linesArr);
            $lines .= implode('', $linesArr);
        }

        return $total ? self::autoloadFilesHeader($total, $lines) : '';
    }

    private static function autoloadFilesHeader(int $count, string $lines): string
    {
        return self::blue($count).' autoloaded file'.($count <= 1 ? '' : 's').$lines;
    }
}
