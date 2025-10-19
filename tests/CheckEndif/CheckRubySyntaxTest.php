<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckEndif;

use Imanghafoori\LaravelMicroscope\Checks\CheckRubySyntax;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CheckRubySyntaxTest extends TestCase
{
    public function setUp(): void
    {
        BasePath::$path = __DIR__;
        copy(__DIR__.'/endif-init.stub', __DIR__.'/test.php');
    }

    public function tearDown(): void
    {
        BasePath::$path = null;
        unlink(__DIR__.'/test.php');
    }

    #[Test]
    public function test_check()
    {
        ErrorPrinter::singleton()->printer = new class
        {
            public function confirm()
            {
                return true;
            }
        };
        $file = PhpFileDescriptor::make(__DIR__.'/test.php');
        CheckRubySyntax::performCheck($file);

        $test = file_get_contents(__DIR__.'/test.php');
        $result = file_get_contents(__DIR__.'/endif-expected.stub');

        $this->assertEquals($result, $test);
    }
}
