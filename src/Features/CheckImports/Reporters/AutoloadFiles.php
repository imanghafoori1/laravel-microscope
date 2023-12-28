<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

class AutoloadFiles
{
    use Reporting;

    /**
     * @param  string  $basePath
     * @param  \Generator  $filesList
     * @return string
     */
    public static function getLines($basePath, $filesList)
    {
        $count = 0;
        $lines = '';

        foreach (iterator_to_array($filesList) as $files) {
            foreach ($files as $file) {
                $count++;
                $file = str_replace($basePath, '', $file);
                $file = str_replace(DIRECTORY_SEPARATOR, '/', $file);
                $lines .= PHP_EOL.'    '.self::hyphen('<fg=green>'.ltrim($file, '/').'</>');
            }
        }

        return $count ? self::autoloadFilesHeader($count, $lines) : '';
    }

    private static function autoloadFilesHeader(int $count, string $lines): string
    {
        return self::blue($count).' autoloaded file'.($count <= 1 ? '' : 's').$lines;
    }
}
