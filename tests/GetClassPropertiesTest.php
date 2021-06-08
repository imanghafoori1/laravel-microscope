<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Analyzers\GetClassProperties;

class GetClassPropertiesTest extends BaseTestClass
{
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

    /** @test */
    public function can_detect_multi_extend()
    {
        [$namespace, $name, $type, $parent, $interfaces] = GetClassProperties::fromFilePath(__DIR__.'/stubs/multi_extend_interface.stub');

        $this->assertEquals("App\Models\Support", $namespace);
        $this->assertEquals('BaseInterface', $name);
        $this->assertEquals(T_INTERFACE, $type);
        $this->assertEquals('AnotherBaseInterface|Arrayable|Jsonable|JsonSerializable', $parent);
    }

    /** @test */
    public function can_detect_multi_extend_1()
    {
        [$namespace, $name, $type, $parent, $interfaces] = GetClassProperties::fromFilePath(__DIR__.'/stubs/interface_sample.stub');

        $this->assertEquals("", $namespace);
        $this->assertEquals('interface_sample', $name);
        $this->assertEquals(T_INTERFACE, $type);
        $this->assertEquals('IncompleteTest', $parent);
    }

    /** @test */
    public function can_detect_simple_classes()
    {
        [$namespace, $name, $type, $parent, $interfaces] = GetClassProperties::fromFilePath(__DIR__.'/stubs/I_am_simple.stub');

        $this->assertEquals("", $namespace);
        $this->assertEquals('I_am_simple', $name);
        $this->assertEquals(T_CLASS, $type);
        $this->assertEquals('', $parent);
    }
}
