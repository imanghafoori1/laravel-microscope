<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;

class FilePathAnalyzerTest extends BaseTestClass
{
    /** @test */
    public function method_normalize_test()
    {
        $path = '/usr/laravel\\\\framework/app\Http\..\..\\..//database';
        $normalizedPath = FilePath::normalize($path);

        $this->assertEquals(implode(DIRECTORY_SEPARATOR, ['', 'usr', 'laravel', 'database']), $normalizedPath);
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
