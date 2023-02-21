<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\Filesystem\FakeFilesystem;
use Imanghafoori\Filesystem\FileManipulator;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Psr4\NamespaceFixer;

class NamespaceCorrectorTest extends BaseTestClass
{
    /** @test */
    public function derive()
    {
        $psr4Path = 'branding_manager/app/';
        $namespace = 'Branding\\';
        $fileName = 'DNS.php';
        $relativePath = "\branding_manager\app\Cert\DNS.php"; // windows path

        $result = ForPsr4LoadedClasses::derive($psr4Path, $relativePath, $namespace, $fileName);

        $this->assertEquals('DNS', $result[0]);
        $this->assertEquals("Branding\Cert\DNS", $result[1]);

        $relativePath = '/branding_manager/app/Cert/DNS.php'; // unix paths
        $result = ForPsr4LoadedClasses::derive($psr4Path, $relativePath, $namespace, $fileName);

        $this->assertEquals('DNS', $result[0]);
        $this->assertEquals("Branding\Cert\DNS", $result[1]);
    }

    /** @test */
    public function fix_namespace()
    {
        FakeFileSystem::reset();
        FileManipulator::$fileSystem = FakeFileSystem::class;
        FileSystem::$fileSystem = FakeFileSystem::class;
        $correctNamespace = 'App\Http\Controllers\Foo';
        $filePath = __DIR__.'/stubs/PostController.stub';
        NamespaceFixer::fix($filePath, 'App\Http\Controllers', $correctNamespace);
        $pattern = '/[\n\s]*<\?php[\s\n]*namespace App\\\Http\\\Controllers\\\Foo;/';
        $this->assertTrue(preg_match($pattern, FakeFilesystem::$putContent[$filePath]) == 1);
    }

    /** @test */
    public function fix_namespace_declare()
    {
        FakeFileSystem::reset();
        FileManipulator::$fileSystem = FakeFileSystem::class;
        FileSystem::$fileSystem = FakeFileSystem::class;
        $from = '';
        $to = 'App\Http\Controllers\Foo';
        $filePath = __DIR__.'/stubs/fix_namespace/declared_no_namespace.stub';
        NamespaceFixer::fix($filePath, $from, $to);
        FakeFilesystem::$files[$filePath][5] = trim(FakeFilesystem::$files[$filePath][5]);
        $this->assertTrue(in_array('namespace '.$to.';', FakeFilesystem::$files[$filePath]));
    }

    /** @test */
    public function fix_namespace_class_with_no_namespace()
    {
        FakeFileSystem::reset();
        FileManipulator::$fileSystem = FakeFileSystem::class;
        FileSystem::$fileSystem = FakeFileSystem::class;

        $from = '';
        $to = 'App\Http\Roo';
        $filePath = __DIR__.'/stubs/fix_namespace/class_no_namespace.stub';
        NamespaceFixer::fix($filePath, $from, $to);
        $pattern = '/[\n\s]*<\?php[\s\n]*namespace App\\\Http\\\Roo;/';
        $this->assertTrue(preg_match($pattern, FakeFilesystem::$files[$filePath][0]) == 1);
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
        NamespaceCalculator::fix(__DIR__.'/stubs/fix_namespace/class_with_namespace.stub', $from, $to);

        // assert
        $result = FakeFileSystem::read_file(__DIR__.'/stubs/fix_namespace/class_with_namespace.stub', "\n");
        $this->assertEquals($result, FakeFileSystem::read_file(__DIR__.'/stubs/fix_namespace/class_with_namespace_2.stub', "\n"));
    }
}
