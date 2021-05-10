<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Analyzers\ClassMethods;
use Imanghafoori\LaravelMicroscope\LaravelMicroscopeServiceProvider;
use Orchestra\Testbench\TestCase;

class AbstractMethodsClassTest extends TestCase
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
    public function check_is_abstract_method_test()
    {
        $class = $this->classToken;
        $this->assertEquals($class['methods'][0]['is_abstract'], true);
        $this->assertEquals($class['methods'][1]['is_abstract'], true);
        $this->assertEquals($class['methods'][2]['is_abstract'], true);
        $this->assertEquals($class['methods'][3]['is_abstract'], true);
        $this->assertEquals($class['methods'][4]['is_abstract'], true);
        $this->assertEquals($class['methods'][5]['is_abstract'], true);
        $this->assertEquals($class['methods'][6]['is_abstract'], true);
        $this->assertEquals($class['methods'][8]['is_abstract'], true);
        $this->assertEquals($class['methods'][9]['is_abstract'], true);
        $this->assertEquals($class['methods'][10]['is_abstract'], true);
        $this->assertEquals($class['methods'][11]['is_abstract'], true);
        $this->assertEquals($class['methods'][12]['is_abstract'], true);
    }

    /** @test */
    public function check_return_types_test()
    {
        $class = $this->classToken;
        //check is nullable return types
        $this->assertEquals($class['methods'][0]['nullable_return_type'], null);
        $this->assertEquals($class['methods'][6]['nullable_return_type'], false);

        $this->assertEquals($class['methods'][0]['returnType'], null);
        $this->assertEquals($class['methods'][6]['returnType'], [311, 'test', 16]);
        $this->assertEquals($class['methods'][7]['returnType'], [311, 'string', 17]);
        $this->assertEquals($class['methods'][8]['returnType'], [311, 'bool', 18]);
        $this->assertEquals($class['methods'][9]['returnType'], [311, 'int', 19]);
        $this->assertEquals($class['methods'][10]['returnType'], [371, 'array', 20]);
        $this->assertEquals($class['methods'][11]['returnType'], [311, 'void', 21]);
        $this->assertEquals($class['methods'][12]['returnType'], [311, 'float', 22]);
    }

    /** @test */
    public function check_visibility_test()
    {
        $class = $this->classToken;
        $this->assertEquals($class['methods'][0]['visibility'][1], 'public');
        $this->assertEquals($class['methods'][1]['visibility'][1], 'public');
        $this->assertEquals($class['methods'][2]['visibility'][1], 'protected');
        $this->assertEquals($class['methods'][3]['visibility'][1], 'public');
        $this->assertEquals($class['methods'][4]['visibility'][1], 'public');
        $this->assertEquals($class['methods'][5]['visibility'][1], 'protected');
        $this->assertEquals($class['methods'][6]['visibility'][1], 'public');
        $this->assertEquals($class['methods'][7]['visibility'][1], 'public');
        $this->assertEquals($class['methods'][8]['visibility'][1], 'public');
        $this->assertEquals($class['methods'][9]['visibility'][1], 'public');
    }

    /** @test */
    public function check_is_static_method_test()
    {
        $class = $this->classToken;
        $this->assertEquals($class['methods'][3]['is_static'], true);
        $this->assertEquals($class['methods'][4]['is_static'], true);
        $this->assertEquals($class['methods'][5]['is_static'], true);
    }

    /** @test  */
    public function abstract_class_general_body_test()
    {
        $class = $this->classToken;
        $this->assertEquals($class['name'], [311, 'abstract_sample', 3]);
        $this->assertCount(13, $class['methods']);
        $this->assertTrue($class['is_abstract']);
        $this->assertEquals($class['type'], 364);
    }

    /**
     * get tokens of stubs classes.
     *
     * @return array
     */
    private function getTokens()
    {
        $string = file_get_contents(__DIR__.'/stubs/abstract_sample_class.php');


        return token_get_all($string);
    }
}
