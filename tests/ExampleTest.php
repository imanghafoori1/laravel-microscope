<?php

namespace Imanghafoori\LaravelSelfTest\Tests;

use Orchestra\Testbench\TestCase;
use Imanghafoori\LaravelSelfTest\LaravelSelfTestServiceProvider;

class ExampleTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaravelSelfTestServiceProvider::class];
    }

    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
