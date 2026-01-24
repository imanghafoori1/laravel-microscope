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
        $count = ChecksOnPsr4Classes::$checkedFilesCount;
        ChecksOnPsr4Classes::$checkedFilesCount = 0;
        $es = $count <= 1 ? '' : 'es';

        if ($count) {
            return CheckImportReporter::blue($count)."class$es";
        } else {
            return '';
        }
    }
}