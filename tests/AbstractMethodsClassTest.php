<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Analyzers\ClassMethods;
use Imanghafoori\LaravelMicroscope\LaravelMicroscopeServiceProvider;
use Orchestra\Testbench\TestCase;
use function range;
use function substr;
use function is_array;
use function array_filter;
use function token_get_all;
use function file_get_contents;

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
        // Checks all the methods are abstract
        foreach( range( 0, 25 ) as $index ) {
            $this->assertTrue( $class['methods'][$index]['is_abstract'] );
        }
        $this->assertFalse( $class['methods'][26]['is_abstract'] );
    }

    /** @test */
    public function check_return_types_test()
    {
        $class = $this->classToken;
        //check is nullable return types
        $this->assertEquals($class['methods'][0]['nullable_return_type'], null);
        $this->assertEquals($class['methods'][6]['nullable_return_type'], false);
        $this->assertEquals($class['methods'][13]['nullable_return_type'], true);

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

    /** @test
     * @dataProvider checkVisibility
     */
    public function check_visibility_test($index,$visibility)
    {
        $class = $this->classToken;
        $this->assertEquals( $class['methods'][$index]['visibility'][1], $visibility );
    }

    public function checkVisibility() : array
    {
        return [
            [0, 'public'],
            [1, 'public'],
            [2, 'protected'],
            [3, 'public'],
            [4, 'public'],
            [5, 'protected'],
            [6, 'public'],
            [7, 'public'],
            [8, 'public'],
            [9, 'public'],
            [22, 'public'],
            [23, 'public'],
            [24, 'protected'],
            [25, 'public'],
        ];
    }

    /** @test */
    public function check_is_static_method_test()
    {
        $class = $this->classToken;
        foreach ([3,4,5,25] as $index){
            $this->assertTrue($class['methods'][$index]['is_static']);
        }
    }

    /** @test  */
    public function abstract_class_general_body_test()
    {
        $class = $this->classToken;
        $this->assertEquals($class['name'], [311, 'abstract_sample', 7]);
        $this->assertCount(27, $class['methods']);
        $this->assertTrue($class['is_abstract']);
        $this->assertEquals($class['type'], 364);
    }

    /** @test */
    public function check_parameter_methods()
    {
        $class = $this->classToken;
        // check function has parameter
        $this->assertEquals($class['methods'][14]['signature'][0][1], '$parameter1');
        // check nullable type cast method parameters
        $this->assertEquals($class['methods'][15]['signature'][0], '?');
        $this->assertEquals($class['methods'][15]['signature'][1][1], 'int');
        $this->assertEquals($class['methods'][15]['signature'][3][1], '$parameter1');
        // check type casting of parameters
        $this->assertEquals($class['methods'][16]['signature'][0][1], 'int');
        // number of parameter
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
        // check method with nullable multi parameter
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
        return token_get_all(file_get_contents(__DIR__.'/stubs/abstract_sample_class.php'));
    }
}
