<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\LaravelMicroscopeServiceProvider;
use Orchestra\Testbench\TestCase;

class BaseTestClass extends TestCase
{
    use CallsPrivateMethods;

    protected function getPackageProviders($app)
    {
        return [LaravelMicroscopeServiceProvider::class];
    }
}
