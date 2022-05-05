<?php

namespace EasyPanel\Support\Contract;

use Illuminate\Support\Facades\Facade;

class BaseFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return static::class;
    }

    public static function shouldProxyTo($class)
    {
        app()->singleton(static::getFacadeAccessor(), $class);
    }
}
