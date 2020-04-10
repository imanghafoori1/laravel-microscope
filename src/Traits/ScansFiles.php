<?php

namespace Imanghafoori\LaravelMicroscope\Traits;

trait ScansFiles
{
    /**
     * {@inheritdoc}
     */
    public function onFileTap($file)
    {
        if ($this->option('detailed')) {
            $this->line("Checking {$file->getRelativePathname()}");
        }
    }
}
