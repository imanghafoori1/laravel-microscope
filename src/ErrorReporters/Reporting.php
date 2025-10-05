<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use JetBrains\PhpStorm\Pure;

trait Reporting
{
    #[Pure]
    public static function green(string $string)
    {
        return '<fg=green>'.$string.'</>';
    }

    #[Pure]
    public static function hyphen($string = '')
    {
        return '   ➖  '.$string;
    }

    #[Pure]
    public static function files($count)
    {
        return '<fg=white> ('.$count.' file'.($count == 1 ? '' : 's').')</>';
    }

    #[Pure]
    public static function addLine($path, $count)
    {
        $output = PHP_EOL.'    '.self::hyphen();
        $output .= self::green(self::normalize($path));
        $output .= self::files($count);

        return $output;
    }

    #[Pure]
    public static function blue($filesCount)
    {
        return self::hyphen().'<fg=blue>'.$filesCount.'</> ';
    }

    #[Pure]
    public static function normalize($dirPath)
    {
        $path = trim(FilePath::normalize(str_replace(BasePath::$path, '', $dirPath)), DIRECTORY_SEPARATOR);

        return str_replace(DIRECTORY_SEPARATOR, '/', $path).'/';
    }

    #[Pure]
    private static function formatLine(PhpFileDescriptor $file): string
    {
        $relPath = $file->path()->relativePath()->getWithUnixDirectorySeprator();

        return PHP_EOL.'    '.self::hyphen('<fg=green>'.$relPath.'</>');
    }

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\DTO\FilesDto  $files
     * @return string[]
     */
    #[Pure]
    public static function formatFiles($files)
    {
        return Loop::map($files->files, fn ($file) => self::formatLine($file));
    }
}
