<?php

namespace Imanghafoori\LaravelMicroscope\Tests\EnforceImports;

use Imanghafoori\LaravelMicroscope\Features\EnforceImports\EnforceImports;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;
use PHPUnit\Framework\TestCase;

class EnforceImportsTest extends TestCase
{
    public function setUp(): void
    {
        copy(__DIR__.'/imports-initial.stub', __DIR__.'/imports.temp');
    }

    public function tearDown(): void
    {
        unlink(__DIR__.'/imports.temp');
    }

    public function testFixFile()
    {
        EnforceImports::setOptions(false, 'U3', function (PhpFileDescriptor $file) {
            $imports = ParseUseStatement::parseUseStatements($file->getTokens());

            return $imports[0] ?: [$imports[1]];
        }, function ($err) {
        });
        $result = EnforceImports::performCheck(
            PhpFileDescriptor::make(__DIR__.DIRECTORY_SEPARATOR.'imports.temp')
        );

        $actual = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'imports.temp');
        $expected = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'imports-expected.stub');
        $this->assertEquals($expected, $actual);
        $this->assertEquals(true, $result);
    }
}
