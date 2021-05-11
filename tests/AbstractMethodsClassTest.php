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
        $this->assertEquals($class['methods'][6]['returnType'][1], 'test');
        $this->assertEquals($class['methods'][7]['returnType'][1], 'string');
        $this->assertEquals($class['methods'][8]['returnType'][1], 'bool');
        $this->assertEquals($class['methods'][9]['returnType'][1], 'int');
        $this->assertEquals($class['methods'][10]['returnType'][1], 'array');
        $this->assertEquals($class['methods'][11]['returnType'][1], 'void');
        $this->assertEquals($class['methods'][12]['returnType'][1], 'float');
        $this->assertEquals($class['methods'][13]['returnType'][1], 'string');
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
        $this->assertCount(22, $class['methods']);
        $this->assertTrue($class['is_abstract']);
        $this->assertEquals($class['type'], 364);
    }

    /** @test */
    public function check_parameter_methods()
    {
        $class = $this->classToken;
        //check function has parameter
        $this->assertEquals($class['methods'][14]['signature'][0][1], '$parameter1');
        //check nullable type cast method parameters
        $this->assertEquals($class['methods'][15]['signature'][0], '?');
        $this->assertEquals($class['methods'][15]['signature'][1][1], 'int');
        $this->assertEquals($class['methods'][15]['signature'][3][1], '$parameter1');
        // check type casting of parameters
        $this->assertEquals($class['methods'][16]['signature'][0][1], 'int');
        //number of parameter
        $signatures = $class['methods'][17]['signature'];
        $parameters = array_filter($signatures, function ($item) {
            if (is_array($item) && substr($item[1], 0, 1) == '$') {
                return true;
            }

            return false;
        });
        $this->assertCount(3, $parameters);
        // check multi parameter with type
        $this->assertEquals($class['methods'][18]['signature'][0][1], '...');
        $this->assertEquals($class['methods'][18]['signature'][1][1], '$parameter2');
        // check multi parameter with type casting
        $this->assertEquals($class['methods'][19]['signature'][0][1], 'string');
        $this->assertEquals($class['methods'][19]['signature'][2][1], '...');
        $this->assertEquals($class['methods'][19]['signature'][3][1], '$parameter1');
        //check method with nullable multi parameter
        $this->assertEquals($class['methods'][20]['signature'][0], '?');
        $this->assertEquals($class['methods'][20]['signature'][1][1], 'string');
        $this->assertEquals($class['methods'][20]['signature'][3][1], '...');
        $this->assertEquals($class['methods'][20]['signature'][4][1], '$parameter1');
        // check default value of parameters
        $this->assertEquals($class['methods'][21]['signature'][0][1], '$parameter1');
        $this->assertEquals($class['methods'][21]['signature'][2], '=');
        $this->assertEquals($class['methods'][21]['signature'][4][1], 'null');
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
