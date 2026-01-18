<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Reports;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\ChecksOnPsr4Classes;
use JetBrains\PhpStorm\Pure;

trait FilesStats
{
    #[Pure]
    private static function getFilesStats(): string
    {
        $filesCount = ChecksOnPsr4Classes::$checkedFilesCount;
        ChecksOnPsr4Classes::$checkedFilesCount = 0;

        return $filesCount ? CheckImportReporter::getFilesStats($filesCount) : '';
    }
}