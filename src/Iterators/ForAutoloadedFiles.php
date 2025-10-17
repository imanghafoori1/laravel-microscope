<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;

class ForAutoloadedFiles
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\CheckSet  $checker
     * @return \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto
     */
    public static function check($checker)
    {
        $autoloadFiles = ComposerJson::autoloadedFilesList();

        return ForFolderPaths::checkFilePaths($autoloadFiles, $checker);
    }
}
