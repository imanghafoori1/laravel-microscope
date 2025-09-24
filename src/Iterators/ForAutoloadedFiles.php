<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;

class ForAutoloadedFiles
{
    /**
     * @param  string  $basePath
     * @param  \Imanghafoori\LaravelMicroscope\Iterators\CheckSet  $checker
     * @return \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto
     */
    public static function check($basePath, $checker)
    {
        $autoloadFiles = ComposerJson::autoloadedFilesList($basePath);

        return ForFolderPaths::checkFilePaths($autoloadFiles, $checker);
    }
}
