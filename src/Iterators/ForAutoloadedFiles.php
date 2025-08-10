<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class ForAutoloadedFiles
{
    /**
     * @return array<string, \Generator<int, PhpFileDescriptor>>
     */
    public static function check($basePath, $checks, $paramProvider, $pathDTO = null)
    {
        $autoloadFiles = ComposerJson::autoloadedFilesList($basePath);

        return ForFolderPaths::checkFilePaths($autoloadFiles, $checks, $paramProvider, $pathDTO);
    }
}
