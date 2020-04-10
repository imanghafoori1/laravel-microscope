<?php

namespace Imanghafoori\LaravelMicroscope\Traits;

trait ScansFiles
{

    /**
     * Logs the file being scanned.
     *
     * @param $file
     */
    function onFileTouch($file)
    {
        $this->line("Scanning {$file->getRelativePathname()}");
    }
}
