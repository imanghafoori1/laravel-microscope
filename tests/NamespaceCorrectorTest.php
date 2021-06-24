<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;

class NamespaceCorrectorTest extends BaseTestClass
{
    /** @test */
    public function read_autoload()
    {
        ComposerJson::$fakeComposerPath = __DIR__.'/stubs/composer_json/composer2.json';

        $expected = [
            'App\\' => 'app/',
            'App2\\' => 'app2/',
        ];

        $this->assertEquals($expected, ComposerJson::readAutoload());
        ComposerJson::$fakeComposerPath = null;
    }

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

    /** @test */
    public function get_namespace_from_relative_path()
    {
        $result = NamespaceCorrector::getNamespaceFromRelativePath('app/Hello.php');
        $this->assertEquals('App\\Hello', $result);

        $result = NamespaceCorrector::getNamespaceFromRelativePath('app/appollo.php');
        $this->assertEquals('App\\appollo', $result);

        $autoload = [
            'App\\'=> 'app/',
            'App\\lication\\'=> 'app/s/',
            'Database\\Seeders\\'=> 'database/seeders/',
        ];

        $result = NamespaceCorrector::getNamespaceFromRelativePath('app/s/Hello.php', $autoload);
        $this->assertEquals('App\\lication\\Hello', $result);

        $result = NamespaceCorrector::getNamespaceFromRelativePath('app/appollo.php', $autoload);
        $this->assertEquals('App\\appollo', $result);
    }
}
