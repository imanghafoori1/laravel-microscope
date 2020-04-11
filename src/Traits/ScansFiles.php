<?php

namespace Imanghafoori\LaravelMicroscope\Traits;

trait ScansFiles
{
    /**
     * {@inheritdoc}
     */
    public function onFileTap($file)
    {
        // @todo better to be an event listener.
        if ($this->option('detailed')) {
            $this->line("Checking {$file->getRelativePathname()}");
        }
    }
}
