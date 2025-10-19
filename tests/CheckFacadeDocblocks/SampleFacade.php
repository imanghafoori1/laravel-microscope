<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckFacadeDocblocks;

use Illuminate\Support\Facades\Facade;

class SampleFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return MySampleRoot::class;
    }
}
