<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Closure;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Symfony\Component\Finder\Finder;

trait FiltersFiles
{
    /**
     * @param  \Symfony\Component\Finder\Finder  $files
     * @param  string  $fileName
     * @param  string  $folder
     * @return \Generator
     */
    private static function filterFiles(Finder $files, $folder, $fileName = null)
    {
        return self::filterItems($files, function ($file) use ($folder, $fileName) {
            return FilePath::contains($file->getPathname(), $folder, $fileName);
        });
    }

    /**
     * @param  $items
     * @param  \Closure  $condition
     * @return \Generator
     */
    private static function filterItems($items, Closure $condition)
    {
        foreach ($items as $item) {
            if ($condition($item)) {
                yield $item;
            }
        }
    }
}
