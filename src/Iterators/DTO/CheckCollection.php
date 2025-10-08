<?php

namespace Imanghafoori\LaravelMicroscope\Iterators\DTO;

use Imanghafoori\LaravelMicroscope\Foundations\Loop;

class CheckCollection
{
    /**
     * @var array<int, class-string<\Imanghafoori\LaravelMicroscope\Check>>
     */
    public $checks = [];

    public function applyOnFile($fileDescriptor)
    {
        Loop::over($this->checks, fn ($check) => $check::check($fileDescriptor));
    }

    public static function make($stats)
    {
        $obj = new self();

        $obj->checks = $stats;

        return $obj;
    }
}
