<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;

class ForAutoloadedFiles
{
    /**
     * @return \Imanghafoori\LaravelMicroscope\Iterators\DTO\StatsDto
     */
    public static function check($basePath, $checks, $paramProvider, $pathDTO = null)
    {
        $autoloadFiles = ComposerJson::autoloadedFilesList($basePath);

        return ForFolderPaths::checkFilePaths($autoloadFiles, $checks, $paramProvider, $pathDTO);
    }
}
