<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;

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

        $this->assertEquals("{$ds}usr{$ds}laravel{$ds}database", $normalizedPath);
        $this->assertEquals("{$ds}usr{$ds}laravel{$ds}database", FilePath::normalize($path2));
        $this->assertEquals("{$ds}usr{$ds}framework{$ds}database", FilePath::normalize($path3));
        $this->assertTrue(! Str::contains($normalizedPath, '\\\\'));
        $this->assertTrue(! Str::contains($normalizedPath, '//'));
        $this->assertTrue(! Str::contains($normalizedPath, '..'));
        $this->assertTrue(! Str::contains($normalizedPath, '../'));
        $this->assertTrue(! Str::contains($normalizedPath, '..\\'));
    }
}
