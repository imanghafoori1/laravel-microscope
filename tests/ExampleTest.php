<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Orchestra\Testbench\TestCase;
use Imanghafoori\LaravelMicroscope\LaravelMicroscopeServiceProvider;

class ExampleTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaravelMicroscopeServiceProvider::class];
    }

    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
