<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\DTO;

class StatsDto
{
    use MakeDto;

    /**
     * @var  array<string, \Generator<int, \Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor>>
     */
    public $stats = [];
}
