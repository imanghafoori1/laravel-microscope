<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckPsr12;

use Imanghafoori\LaravelMicroscope\Features\CheckPsr12\CheckPsr12Command;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CheckPsr12CommandTest extends TestCase
{
    public static $file = __DIR__.DIRECTORY_SEPARATOR.'psr12.php';

    public function setUp(): void
    {
        copy(__DIR__.DIRECTORY_SEPARATOR.'psr12.stub', self::$file);
        $_SESSION['msg'] = 0;
    }

    public function tearDown(): void
    {
        unlink(self::$file);
        unset($_SESSION['msg']);
    }

    #[Test]
    public function basic()
    {
        // arrange:
        $command = new CheckPsr12Command();
        $iterator = new class {
            public function formatPrintPsr4Classmap()
            {
                $_SESSION['msg']++;
            }
        };

        // act:
        $command->handleCommand($iterator);

        // assert:
        $this->assertEquals($_SESSION['msg'], 1);

        $check = $command->checks[0];

        $file = PhpFileDescriptor::make(self::$file);
        $check::check($file);

        $this->assertEquals(
            file_get_contents(self::$file),
            file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'psr12.expected')
        );
    }
}