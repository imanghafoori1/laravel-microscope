<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO;

class FilesDto
{
    /**
     * @var \Generator<int, \Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor>
     */
    public $files;

    public static function make($data)
    {
        $obj = new self();
        $obj->files = $data;

        return $obj;
    }
}
