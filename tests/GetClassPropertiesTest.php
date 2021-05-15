<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Analyzers\GetClassProperties;
use Imanghafoori\LaravelMicroscope\LaravelMicroscopeServiceProvider;
use Orchestra\Testbench\TestCase;

class GetClassPropertiesTest extends TestCase
{
    use CallsPrivateMethods;

    protected function getPackageProviders($app)
    {
        return [LaravelMicroscopeServiceProvider::class];
    }

    /** @test */
    public function can_detect_method_visibility()
    {
        [$namespace, $name, $type, $parent, $interfaces] = GetClassProperties::fromFilePath(__DIR__.'/stubs/HomeController.php');

        $this->assertEquals("App\Http\Controllers", $namespace);
        $this->assertEquals('HomeController', $name);
        $this->assertEquals(T_CLASS, $type);
        $this->assertEquals('Controller', $parent);
        $this->assertEquals('Countable|MyInterface', $interfaces);
    }
}
