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

    /**
     * get tokens of stubs classes.
     *
     * @param  string  $path  path of stub file
     * @return array
     */
    protected function getTokens(string $path): array
    {
        return token_get_all(file_get_contents(__DIR__.$path));
    }
}
