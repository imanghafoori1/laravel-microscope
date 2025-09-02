<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\DTO;

class Psr4StatsDTO
{
    use MakeDto;

    /**
     * @var array<string, array<string, (callable(): int)>>
     */
    public $stats = [];
}
