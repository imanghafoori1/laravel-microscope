<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Symfony\Component\Finder\Finder;

trait FiltersFiles
{
    /**
     * @param \Symfony\Component\Finder\Finder $files
     * @param string $fileName
     * @param string $folder
     * @return \Generator
     */
    private static function filterFiles(Finder $files, $fileName, $folder)
    {
        return self::filterItems($files, function ($file) use ($fileName, $folder) {
            return FilePath::contains($file->getPathname(), $fileName, $folder);
        });
    }

    private static function filterItems(array $items, \Closure $condition)
    {
        foreach ($items as $item) {
            if ($condition($item)) {
                yield $item;
            }
        }
    }
}