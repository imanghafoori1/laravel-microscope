<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckRouteCalls;

use Imanghafoori\LaravelMicroscope\Checks\CheckRouteCalls;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use PHPUnit\Framework\TestCase;

class CheckRouteCallsTest extends TestCase
{
    public function setUp(): void
    {
        $_SERVER['routes'] = [];
    }

    public function tearDown(): void
    {
        unset($_SERVER['routes']);
    }

    public function testCheckRouteCalls()
    {
        CheckRouteCalls::$router = new class
        {
            public function getByName($route)
            {
                $_SERVER['routes'][] = $route;

                return null;
            }
        };
        $file = PhpFileDescriptor::make(__DIR__.'/route_call.stub');

        CheckRouteCalls::check($file);

        $this->assertEquals(['rrr'], $_SERVER['routes']);
    }
}