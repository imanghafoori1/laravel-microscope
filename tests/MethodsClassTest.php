<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use function file_get_contents;
use Imanghafoori\LaravelMicroscope\Analyzers\ClassMethods;
use Imanghafoori\LaravelMicroscope\LaravelMicroscopeServiceProvider;
use Orchestra\Testbench\TestCase;
use function token_get_all;

class MethodsClassTest extends TestCase
{
    /** @var array */
    private $classToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classToken = ClassMethods::read($this->getTokens());
    }

    protected function getPackageProviders($app)
    {
        return [LaravelMicroscopeServiceProvider::class];
    }

    /** @test */
    public function true_is_true()
    {
        $class = $this->classToken;

        $this->assertFalse($class['is_abstract']);
        $this->assertEquals($class['type'], T_CLASS);
        $this->assertCount(8, $class['methods']);
        $this->assertEquals($class['methods'][0]['is_abstract'], false);

        $this->assertEquals($class['methods'][0]['returnType'], null);
        $this->assertEquals($class['methods'][0]['nullable_return_type'], null);
        $this->assertEquals($class['methods'][0]['signature'], []);
        $this->assertEquals($class['methods'][0]['name'], [311, 'hello1', 5]);
    }

    /** @test */
    private function check_is_static_method()
    {
        $class = $this->classToken;

        $this->assertEquals($class['methods'][0]['is_static'], false);
        $this->assertEquals($class['methods'][4]['is_static'], true );
        $this->assertEquals($class['methods'][5]['is_static'], true );
        $this->assertEquals($class['methods'][6]['is_static'], true );
        $this->assertEquals($class['methods'][7]['is_static'], true );
    }

    /** @test */
    private function check_visibility()
    {
        $class = $this->classToken;

        $this->assertEquals($class['methods'][0]['visibility'][1], 'public' );
        $this->assertEquals($class['methods'][1]['visibility'][1], 'protected' );
        $this->assertEquals($class['methods'][2]['visibility'][1], 'private' );
        $this->assertEquals($class['methods'][3]['visibility'][1], 'public' );
        $this->assertEquals($class['methods'][4]['visibility'][1], 'public' );
        $this->assertEquals($class['methods'][5]['visibility'][1], 'protected' );
        $this->assertEquals($class['methods'][6]['visibility'][1], 'private' );
        $this->assertEquals($class['methods'][7]['visibility'][1], 'public' );
    }

    /**
     * get tokens of stubs classes.
     *
     * @return array
     */
    private function getTokens()
    {
        return token_get_all(file_get_contents(__DIR__.'/stubs/sample_class.php'));
    }
}
