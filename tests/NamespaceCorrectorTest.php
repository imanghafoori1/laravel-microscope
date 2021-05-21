<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;

class NamespaceCorrectorTest extends BaseTestClass
{
    /** @test */
    public function can_extract_namespace()
    {
        $ns = 'Imanghafoori\LaravelMicroscope\Analyzers';
        $class = "$ns\NamespaceCorrector";

        $this->assertEquals($ns, NamespaceCorrector::getNamespaceFromFullClass($class));
        $this->assertEquals('', NamespaceCorrector::getNamespaceFromFullClass('A'));
        $this->assertEquals('B', NamespaceCorrector::getNamespaceFromFullClass('B\A'));
    }

    /** @test */
    public function can_detect_same_namespaces()
    {
        $ns = 'Imanghafoori\LaravelMicroscope\Analyzers';
        $class1 = "$ns\Iman";
        $class2 = "$ns\Ghafoori";
        $class3 = "$ns\Hello\Ghafoori";

        $this->assertEquals(true, NamespaceCorrector::haveSameNamespace('A', 'A'));
        $this->assertEquals(true, NamespaceCorrector::haveSameNamespace('A', 'B'));
        $this->assertEquals(true, NamespaceCorrector::haveSameNamespace($class1, $class2));
        $this->assertEquals(false, NamespaceCorrector::haveSameNamespace($class1, $class3));
        $this->assertEquals(false, NamespaceCorrector::haveSameNamespace($class1, 'Faalse'));
    }
}
