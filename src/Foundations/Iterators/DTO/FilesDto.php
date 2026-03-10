<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO;

class FilesDto
{
    /**
     * @var \Generator<int, \Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor>
     */
    public $files;

    /**
     * @return self
     */
    public static function make($files)
    {
        $obj = new self();
        $obj->files = $files;

        return $obj;
    }
}
