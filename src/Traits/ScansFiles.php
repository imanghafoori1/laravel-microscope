<?php

namespace Imanghafoori\LaravelMicroscope\Traits;

trait ScansFiles
{

    /**
     * @inheritDoc
     */
    function onFileTap($file)
    {
        if ($this->option('detailed')) {
            $this->line("Checking {$file->getRelativePathname()}");
        }
    }
}
