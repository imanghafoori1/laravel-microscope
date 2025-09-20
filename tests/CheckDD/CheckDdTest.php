<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckDD;

use Imanghafoori\LaravelMicroscope\Features\CheckDD\CheckDD;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use PHPUnit\Framework\TestCase;

class CheckDdTest extends TestCase
{
    public static $errors = [];

    public function test_check_dd()
    {
        $file = PhpFileDescriptor::make(__DIR__.'/dd-init.stub');

        CheckDD::$onErrorCallback = function (PhpFileDescriptor $file, $token) {
            self::$errors[] = [$file, $token];
        };

        $result = CheckDD::performCheck($file);
        $this->assertEquals([
            T_STRING,
            'dd',
            3,
        ], self::$errors[0][1]);

        $this->assertEquals([
            T_STRING,
            'dump',
            4,
        ], self::$errors[1][1]);

        $this->assertEquals(true, $result);
        $this->assertCount(2, self::$errors);
    }
}
