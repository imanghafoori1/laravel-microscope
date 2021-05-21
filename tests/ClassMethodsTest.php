<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Analyzers\ClassMethods;

class ClassMethodsTest extends BaseTestClass
{
    /** @test */
    public function can_detect_method_visibility()
    {
        $string = file_get_contents(__DIR__.'/stubs/sample_class.php');
        $tokens = token_get_all($string);

        $class = ClassMethods::read($tokens);

        $this->assertEquals(false, $class['is_abstract']);
        $this->assertEquals(T_CLASS, $class['type']);
        $this->assertCount(8, $class['methods']);
        $this->assertEquals(false, $class['methods'][0]['is_abstract']);
        $this->assertEquals(false, $class['methods'][0]['is_static']);
        $this->assertEquals(null, $class['methods'][0]['returnType']);
        $this->assertEquals(null, $class['methods'][0]['nullable_return_type']);
        $this->assertEquals([], $class['methods'][0]['signature']);
        $this->assertEquals([311, 'hello1', 5], $class['methods'][0]['name']);

        $this->assertEquals('public', $class['methods'][0]['visibility'][1]);
        $this->assertEquals('protected', $class['methods'][1]['visibility'][1]);
        $this->assertEquals('private', $class['methods'][2]['visibility'][1]);
        $this->assertEquals('public', $class['methods'][3]['visibility'][1]);

        $this->assertEquals('public', $class['methods'][4]['visibility'][1]);
        $this->assertEquals('protected', $class['methods'][5]['visibility'][1]);
        $this->assertEquals('private', $class['methods'][6]['visibility'][1]);
        $this->assertEquals('public', $class['methods'][7]['visibility'][1]);

        $this->assertEquals(true, $class['methods'][4]['is_static']);
        $this->assertEquals(true, $class['methods'][5]['is_static']);
        $this->assertEquals(true, $class['methods'][6]['is_static']);
        $this->assertEquals(true, $class['methods'][7]['is_static']);
    }

    /** @test */
    public function can_detect_method_visibility_on_interfaces()
    {
        $string = file_get_contents(__DIR__.'/stubs/PasswordBroker.php');
        $tokens = token_get_all($string);

        $class = ClassMethods::read($tokens);

        $this->assertCount(2, $class['methods']);
        $this->assertEquals(T_INTERFACE, $class['type']);
        $this->assertEquals('public', $class['methods'][0]['visibility'][1]);
        $this->assertEquals('public', $class['methods'][1]['visibility'][1]);
    }

    /** @test */
    public function can_detect_methods_on_traits()
    {
        $string = file_get_contents(__DIR__.'/stubs/Authenticatable.php');
        $tokens = token_get_all($string);

        $trait = ClassMethods::read($tokens);

        $this->assertCount(6, $trait['methods']);
        $this->assertEquals(T_TRAIT, $trait['type']);
        //check visibility
        $this->assertEquals('public', $trait['methods'][0]['visibility'][1]);
        $this->assertEquals('public', $trait['methods'][1]['visibility'][1]);
        //check return type
        $this->assertEquals('string', $trait['methods'][5]['returnType'][1]);
        $this->assertStringContainsString('return $this->rememberTokenName;', $trait['methods'][5]['body']);
    }
}
