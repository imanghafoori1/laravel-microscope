<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Analyzers\ClassMethods;
use Imanghafoori\LaravelMicroscope\LaravelMicroscopeServiceProvider;
use Orchestra\Testbench\TestCase;

class ClassMethodsTest extends TestCase
{
    use CallsPrivateMethods;

    protected function getPackageProviders($app)
    {
        return [LaravelMicroscopeServiceProvider::class];
    }

    /** @test */
    public function can_detect_method_visibility()
    {
        $string = file_get_contents(__DIR__.'/stubs/sample_class.php');
        $tokens = token_get_all($string);

        $class = ClassMethods::read($tokens);

        $this->assertFalse($class['is_abstract']);
        $this->assertEquals($class['type'], T_CLASS);
        $this->assertCount(8, $class['methods']);
        $this->assertEquals($class['methods'][0]['is_abstract'], false);
        $this->assertEquals($class['methods'][0]['is_static'], false);
        $this->assertEquals($class['methods'][0]['returnType'], null);
        $this->assertEquals($class['methods'][0]['nullable_return_type'], null);
        $this->assertEquals($class['methods'][0]['signature'], []);
        $this->assertEquals($class['methods'][0]['name'], [311, 'hello1', 5]);

        $this->assertEquals($class['methods'][0]['visibility'][1], 'public');
        $this->assertEquals($class['methods'][1]['visibility'][1], 'protected');
        $this->assertEquals($class['methods'][2]['visibility'][1], 'private');
        $this->assertEquals($class['methods'][3]['visibility'][1], 'public');

        $this->assertEquals($class['methods'][4]['visibility'][1], 'public');
        $this->assertEquals($class['methods'][5]['visibility'][1], 'protected');
        $this->assertEquals($class['methods'][6]['visibility'][1], 'private');
        $this->assertEquals($class['methods'][7]['visibility'][1], 'public');

        $this->assertEquals($class['methods'][4]['is_static'], true);
        $this->assertEquals($class['methods'][5]['is_static'], true);
        $this->assertEquals($class['methods'][6]['is_static'], true);
        $this->assertEquals($class['methods'][7]['is_static'], true);
    }
}
