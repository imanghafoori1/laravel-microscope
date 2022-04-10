<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\FileSystem\FileManipulator;
use Imanghafoori\LaravelMicroscope\FileSystem\FileSystem;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileSystem\FakeFileSystem;
use Imanghafoori\LaravelMicroscope\Psr4\NamespaceCorrector;

class NamespaceCorrectorTest extends BaseTestClass
{
    /** @test */
    public function calculate_correct_namespace()
    {
        $ds = DIRECTORY_SEPARATOR;
        $path = "app{$ds}Hello{$ds}T.php";
        $r = NamespaceCorrector::calculateCorrectNamespace($path, 'app/', 'App\\');
        $this->assertEquals("App\Hello", $r);

        $r = NamespaceCorrector::calculateCorrectNamespace($path, 'app', 'App\\');
        $this->assertEquals("App\Hello", $r);

        $r = NamespaceCorrector::calculateCorrectNamespace($path, 'app/Hello', 'Foo\\');
        $this->assertEquals('Foo', $r);

        $r = NamespaceCorrector::calculateCorrectNamespace($path, 'app/Hello', 'Foo\\');
        $this->assertEquals('Foo', $r);

        $r = NamespaceCorrector::calculateCorrectNamespace("app{$ds}Hello{$ds}Hello{$ds}T.php", 'app/Hello', 'Foo\\');
        $this->assertEquals("Foo\Hello", $r);
    }

    /** @test */
    public function read_autoload()
    {
        ComposerJson::$composerPath = __DIR__.'/stubs/composer_json/2';

        $expected = [
            'App\\' => 'app/',
            'App2\\' => 'app2/',
        ];

        $this->assertEquals($expected, ComposerJson::readAutoload());
        ComposerJson::$composerPath = null;
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
    public function fix_namespace()
    {
        FileManipulator::$fileSystem = FakeFileSystem::class;
        FileSystem::$fileSystem = FakeFileSystem::class;
        $correctNamespace = 'App\Http\Controllers\Foo';
        NamespaceCorrector::fix(__DIR__.'./stubs/PostController.stub', 'App\Http\Controllers', $correctNamespace);

        $result = strpos(FakeFileSystem::read_file(__DIR__.'./stubs/PostController.stub'), 'namespace App\Http\Controllers\Foo;');

        $this->assertTrue($result > 0);
    }

    /** @test */
    public function fix_namespace_declare()
    {
        FakeFileSystem::reset();
        FileManipulator::$fileSystem = FakeFileSystem::class;
        FileSystem::$fileSystem = FakeFileSystem::class;
        $from = '';
        $to = 'App\Http\Controllers\Foo';
        NamespaceCorrector::fix(__DIR__.'./stubs/fix_namespace/declared_no_namespace.stub', $from, $to);

        $result = FakeFileSystem::read_file(__DIR__.'./stubs/fix_namespace/declared_no_namespace.stub', "\n");

        $this->assertEquals($result, FakeFileSystem::read_file(__DIR__.'./stubs/fix_namespace/declared_with_namespace.stub', "\n"));
    }

    /** @test */
    public function fix_namespace_class_with_no_namespace()
    {
        FakeFileSystem::reset();
        FileManipulator::$fileSystem = FakeFileSystem::class;
        FileSystem::$fileSystem = FakeFileSystem::class;

        $from = '';
        $to = 'App\Http\Controllers\Foo';
        NamespaceCorrector::fix(__DIR__.'./stubs/fix_namespace/class_no_namespace.stub', $from, $to);

        $result = FakeFileSystem::read_file(__DIR__.'./stubs/fix_namespace/class_no_namespace.stub', "\n");

        $this->assertEquals($result, FakeFileSystem::read_file(__DIR__.'./stubs/fix_namespace/class_with_namespace.stub', "\n"));
    }

    /** @test */
    public function fix_namespace_class_with_bad_namespace()
    {
        // arrange
        FakeFileSystem::reset();
        FileSystem::$fileSystem = FakeFileSystem::class;
        FileManipulator::$fileSystem = FakeFileSystem::class;

        // act
        $from = 'App\Http\Controllers\Foo';
        $to = 'App\Http\Roo';
        NamespaceCorrector::fix(__DIR__.'./stubs/fix_namespace/class_with_namespace.stub', $from, $to);

        // assert
        $result = FakeFileSystem::read_file(__DIR__.'./stubs/fix_namespace/class_with_namespace.stub', "\n");
        $this->assertEquals($result, FakeFileSystem::read_file(__DIR__.'./stubs/fix_namespace/class_with_namespace_2.stub', "\n"));
    }

    /** @test */
    public function get_namespace_from_relative_path()
    {
        $result = NamespaceCorrector::getNamespacedClassFromPath('app/Hello.php');
        $this->assertEquals('App\\Hello', $result);

        $result = NamespaceCorrector::getNamespacedClassFromPath('app/appollo.php');
        $this->assertEquals('App\\appollo', $result);

        $autoload = [
            'App\\'=> 'app/',
            'App\\lication\\'=> 'app/s/',
            'Test\\'=> 'app/d/',
            'Database\\Seeders\\'=> 'database/seeders/',
        ];

        $result = NamespaceCorrector::getNamespacedClassFromPath('app/s/Hello.php', $autoload);
        $this->assertEquals('App\\lication\\Hello', $result);

        $result = NamespaceCorrector::getNamespacedClassFromPath('app/appollo.php', $autoload);
        $this->assertEquals('App\\appollo', $result);

        $result = NamespaceCorrector::getNamespacedClassFromPath('app/d/appollo.php', $autoload);
        $this->assertEquals('Test\\appollo', $result);
    }
}
