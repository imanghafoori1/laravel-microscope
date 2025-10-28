<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

class SampleComposerJson
{
    public function readAutoload()
    {
        return [
            '/' => ['App\\' => 'app/'],
        ];
    }

    public function readAutoloadClassMap()
    {
        return [
            '/' => [],
        ];
    }

    public function autoloadedFilesList()
    {
        return [
            '/' => [],
        ];
    }
}
