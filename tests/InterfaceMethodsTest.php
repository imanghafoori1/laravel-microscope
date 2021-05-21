<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Analyzers\ClassMethods;

class InterfaceMethodsTest extends BaseTestClass
{
    /** @test */
    public function check_methods_has_no_parameters_test()
    {
        $class = ClassMethods::read($this->getTokens('/stubs/interface_sample.stub'));
        $methods = $class['methods'];

        $this->assertEquals('', $methods[0]['body']);
        $this->assertEquals('', $methods[1]['body']);
        $this->assertEquals('', $methods[2]['body']);
        $this->assertEquals('', $methods[3]['body']);
        $this->assertEquals('', $methods[4]['body']);
        $this->assertEquals('', $methods[5]['body']);
        $this->assertEquals('', $methods[6]['body']);
        $this->assertEquals('', $methods[7]['body']);
        $this->assertEquals('', $methods[8]['body']);
        $this->assertEquals('', $methods[9]['body']);
        $this->assertEquals('', $methods[10]['body']);
        $this->assertEquals('', $methods[11]['body']);
        $this->assertEquals('', $methods[12]['body']);
        $this->assertEquals('', $methods[13]['body']);
        $this->assertEquals('', $methods[14]['body']);
        $this->assertEquals('', $methods[15]['body']);
        $this->assertEquals('', $methods[16]['body']);
        $this->assertEquals('', $methods[17]['body']);
        $this->assertEquals('', $methods[18]['body']);
        $this->assertEquals('', $methods[19]['body']);
    }

    /** @test */
    public function check_return_types_test()
    {
        $class = ClassMethods::read($this->getTokens('/stubs/interface_sample.stub'));
        $methods = $class['methods'];

        // check is nullable return types
        $this->assertNull($methods[0]['nullable_return_type']);
        $this->assertFalse($methods[6]['nullable_return_type']);
        $this->assertTrue($methods[11]['nullable_return_type']);

        $this->assertNull($methods[0]['returnType']);
        $this->assertEquals('test', $methods[4]['returnType'][1]);
        $this->assertEquals('string', $methods[5]['returnType'][1]);
        $this->assertEquals('bool', $methods[6]['returnType'][1]);
        $this->assertEquals('int', $methods[7]['returnType'][1]);
        $this->assertEquals('array', $methods[8]['returnType'][1]);
        $this->assertEquals('void', $methods[9]['returnType'][1]);
        $this->assertEquals('float', $methods[10]['returnType'][1]);
        $this->assertEquals('string', $methods[11]['returnType'][1]);
    }

    /** @test */
    public function check_parameter_methods_test()
    {
        $class = ClassMethods::read($this->getTokens('/stubs/interface_sample.stub'));
        $methods = $class['methods'];

        // check function has parameter
        $this->assertEquals('$parameter1', $methods[12]['signature'][0][1]);
        // check nullable type cast method parameters
        $this->assertEquals('?', $methods[13]['signature'][0]);
        $this->assertEquals('int', $methods[13]['signature'][1][1]);
        $this->assertEquals('$parameter1', $methods[13]['signature'][3][1]);
        // check type hinting of parameters
        $this->assertEquals('int', $methods[14]['signature'][0][1]);
        // number of parameter
        $signatures = $methods[15]['signature'];
        $parameters = array_filter($signatures, function ($item) {
            return is_array($item) && substr($item[1], 0, 1) == '$';
        });

        $this->assertCount(3, $parameters);
        // check multi parameter with type
        $this->assertEquals('...', $methods[16]['signature'][0][1]);
        $this->assertEquals('$parameter2', $methods[16]['signature'][1][1]);

        // check multi parameter with type casting
        $this->assertEquals('string', $methods[17]['signature'][0][1]);
        $this->assertEquals('...', $methods[17]['signature'][2][1]);
        $this->assertEquals('$parameter1', $methods[17]['signature'][3][1]);

        // check method with nullable multi parameter
        $this->assertEquals('?', $methods[18]['signature'][0]);
        $this->assertEquals('string', $methods[18]['signature'][1][1]);
        $this->assertEquals('...', $methods[18]['signature'][3][1]);
        $this->assertEquals('$parameter1', $methods[18]['signature'][4][1]);

        // check default value of parameters
        $this->assertEquals('$parameter1', $methods[19]['signature'][0][1]);
        $this->assertEquals('=', $methods[19]['signature'][2]);
        $this->assertEquals('null', $methods[19]['signature'][4][1]);
    }

    /** @test  */
    public function interface_general_body_test()
    {
        $class = ClassMethods::read($this->getTokens('/stubs/interface_sample.stub'));

        $this->assertEquals([T_STRING, 'interface_sample', 8], $class['name']);
        $this->assertEquals(T_INTERFACE, $class['type']);
        $this->assertArrayNotHasKey('is_abstract', $class);
        $this->assertCount(20, $class['methods']);
    }

    /**
     * get tokens of stubs classes.
     *
     * @param string $path path of stub file
     *
     * @return array
     */
    private function getTokens(string $path): array
    {
        return token_get_all(file_get_contents(__DIR__.$path));
    }
}
