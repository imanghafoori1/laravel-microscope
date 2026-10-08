<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Iterators;

use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;

class ForFolderPaths extends BaseIterator
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\CheckSet  $checker
     * @param  array<string, \Generator<int, string>>  $dirsList
     * @return array<string, \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\StatsDto>
     */
    public static function check(CheckSet $checker, $dirsList)
    {
        return Loop::map($dirsList, static fn ($dirs, $listName) => ForFilePaths::check(
            Paths::getAbsFilePaths($dirs, $checker->pathDTO), $checker
        ));
    }
}
