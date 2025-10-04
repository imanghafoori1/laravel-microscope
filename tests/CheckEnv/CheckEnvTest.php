<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckEnv;

use Imanghafoori\LaravelMicroscope\Features\CheckEnvCalls\EnvCallsCheck;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use PHPUnit\Framework\TestCase;

class CheckEnvTest extends TestCase
{
    public static $error = [];

    public function testCheckEnv()
    {
        $file = PhpFileDescriptor::make(__DIR__.'/env-call.stub');
        EnvCallsCheck::$onErrorCallback = function ($name, $absPath, $lineNumber) {
            self::$error[] = [$name, $absPath, $lineNumber];
        };
        $result = EnvCallsCheck::performCheck($file);

        $this->assertTrue($result);
        $this->assertCount(1, self::$error);
        $this->assertEquals('env', self::$error[0][0]);
        $this->assertEquals(3, self::$error[0][2]);
    }
}
