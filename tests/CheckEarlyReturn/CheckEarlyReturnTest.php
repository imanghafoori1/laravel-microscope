<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckEarlyReturn;

use Imanghafoori\LaravelMicroscope\Checks\CheckEarlyReturn;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CheckEarlyReturnTest extends TestCase
{
    public function setUp(): void
    {
        BasePath::$path = __DIR__;
        copy(__DIR__.'/check_early.stub', __DIR__.'/test.php');
        $_SERVER['routes'] = [];
    }

    public function tearDown(): void
    {
        unlink(__DIR__.'/test.php');
        unset($_SERVER['routes']);
    }

    #[Test]
    public function checkEarlyReturn()
    {
        ErrorPrinter::singleton()->printer = new class
        {
            public function confirm()
            {
                return true;
            }
        };
        $file = PhpFileDescriptor::make(__DIR__.'/test.php');
        CheckEarlyReturn::$params['nofix'] = false;
        CheckEarlyReturn::$params['nofixCallback'] = function () {};
        CheckEarlyReturn::$params['fixCallback'] = function () {};
        CheckEarlyReturn::check($file);

        $actual = file_get_contents(__DIR__.'/test.php');
        $expected = file_get_contents(__DIR__.'/check_early-final.stub');

        $this->assertEquals($expected, $actual);
    }
}
