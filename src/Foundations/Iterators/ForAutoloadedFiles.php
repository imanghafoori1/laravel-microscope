<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Iterators;

use Imanghafoori\LaravelMicroscope\Foundations\Analyzers\ComposerJson;

class ForAutoloadedFiles
{
    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\CheckSet  $checker
     * @return \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\StatsDto
     */
    public static function check($checker)
    {
        $autoloadFiles = ComposerJson::autoloadedFilesList();

        return ForFolderPaths::checkFilePaths($autoloadFiles, $checker);
    }
}
