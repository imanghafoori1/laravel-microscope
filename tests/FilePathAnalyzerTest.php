<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;

class FilePathAnalyzerTest extends BaseTestClass
{
    /** @test */
    public function method_normalize_test()
    {
        $path = '/usr/laravel\\\\framework/app\Http\..\..\\..//database';
        $path2 = '\usr\laravel\framework/app\Http\..\..\\..//database';
        $path3 = '\usr\laravel\..\framework/app\Http\..\\..//database';
        $normalizedPath = FilePath::normalize($path);
        $ds = DIRECTORY_SEPARATOR;

        $this->assertEquals( "{$ds}usr{$ds}laravel{$ds}database", $normalizedPath);
        $this->assertEquals( "{$ds}usr{$ds}laravel{$ds}database", FilePath::normalize($path2));
        $this->assertEquals( "{$ds}usr{$ds}framework{$ds}database", FilePath::normalize($path3));
        $this->assertStringNotContainsString('\\\\', $normalizedPath);
        $this->assertStringNotContainsString('//', $normalizedPath);
        $this->assertStringNotContainsString('..', $normalizedPath);
        $this->assertStringNotContainsString('../', $normalizedPath);
        $this->assertStringNotContainsString('..\\', $normalizedPath);
    }

    /** @test */
    public function method_getRelativePath_test()
    {
        $path = base_path().'/database/factories/';
        $normalizedPath = FilePath::getRelativePath($path);

        $this->assertEquals('/database/factories/', $normalizedPath);
        $this->assertStringNotContainsString(base_path(), $normalizedPath);

        $path = base_path().'/database/factories';
        $normalizedPath = FilePath::getRelativePath($path);

        $this->assertEquals('/database/factories', $normalizedPath);
        $this->assertStringNotContainsString(base_path(), $normalizedPath);
    }
}
