<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use JetBrains\PhpStorm\Pure;

trait Reporting
{
    #[Pure]
    public static function hyphen($string = '')
    {
        return '   ➖  '.$string;
    }

    #[Pure]
    public static function files($count)
    {
        $s = $count === 1 ? '' : 's';

        return Color::white(" ($count file$s)");
    }

    #[Pure]
    public static function addLine($path, $count)
    {
        $output = PHP_EOL.'    '.self::hyphen();
        $output .= Color::green(self::normalize($path));
        $output .= self::files($count);

        return $output;
    }

    #[Pure]
    public static function blue($filesCount)
    {
        return self::hyphen().Color::blue($filesCount).' ';
    }

    #[Pure]
    public static function normalize($dirPath)
    {
        $ds = DIRECTORY_SEPARATOR;
        $path = trim(FilePath::normalize(FilePath::getRelativePath($dirPath)), $ds);

        return str_replace($ds, '/', $path).'/';
    }

    #[Pure]
    private static function formatLine(PhpFileDescriptor $file): string
    {
        $relPath = $file->path()->relativePath()->getWithUnixDirectorySeprator();

        return PHP_EOL.'    '.self::hyphen(Color::green($relPath));
    }

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\FilesDto  $files
     * @return string[]
     */
    #[Pure]
    public static function formatFiles($files)
    {
        return Loop::map($files->files, fn ($file) => self::formatLine($file));
    }
}
