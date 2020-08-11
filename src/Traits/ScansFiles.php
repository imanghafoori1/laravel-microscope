<?php

namespace Imanghafoori\LaravelMicroscope\Traits;

trait ScansFiles
{
    /**
     * {@inheritdoc}
     */
    public function onFileTap($path)
    {
        // @todo better to be an event listener.
        if ($this->option('detailed')) {
            $this->line('Checking '.$path);
        }
    }
}
