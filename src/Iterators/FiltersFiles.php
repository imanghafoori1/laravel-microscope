<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Closure;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;

trait FiltersFiles
{
    /**
     * @param  \Symfony\Component\Finder\Finder|array  $files
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return \Generator<int, \Symfony\Component\Finder\SplFileInfo>
     */
    private static function filterFiles($files, $pathDTO)
    {
        return self::filterItems($files, fn ($file) => FilePath::contains($file->getPathname(), $pathDTO));
    }

    /**
     * @param  \Generator<int, string>|string[]  $items
     * @param  \Closure  $condition
     * @return \Generator<int, mixed>
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
