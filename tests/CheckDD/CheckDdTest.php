<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckDD;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckDD\CheckDD;
use Imanghafoori\LaravelMicroscope\Features\CheckDD\CheckDDCommand;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CheckDdTest extends TestCase
{
    public static $errors = [];

    #[Test]
    public function check_dd()
    {
        $file = PhpFileDescriptor::make(__DIR__.'/dd-init.stub');

        CheckDD::$onErrorCallback = function (PhpFileDescriptor $file, $token) {
            self::$errors[] = [$file, $token];
        };

        $result = CheckDD::performCheck($file);
        $this->assertEquals([T_STRING, 'dd', 3,], self::$errors[0][1]);
        $this->assertEquals([T_STRING, 'dump', 4,], self::$errors[1][1]);
        $this->assertEquals([T_STRING, 'dump', 5,], self::$errors[2][1]);
        $this->assertEquals([T_STRING, 'dump', 6,], self::$errors[3][1]);

        $this->assertTrue($result);
        $this->assertCount(4, self::$errors);
    }

    #[Test]
    public function check_dd_command()
    {
        $command = new CheckDDCommand();

        $iterator = new class
        {
            public $print;

            public $count = 0;

            public function forComposerLoadedFiles()
            {
                $this->count++;

                return 'forComposerLoadedFiles';
            }

            public function forMigrationsAndConfigs()
            {
                $this->count++;

                return 'forMigrationsAndConfigs';
            }

            public function forRoutes()
            {
                $this->count++;

                return 'forRoutes';
            }

            public function forBladeFiles()
            {
                $this->count++;

                return 'forBladeFiles';
            }

            public function printAll($array)
            {
                $this->print = $array;
            }
        };

        $command->handleCommand($iterator);

        $this->assertEquals(3, $iterator->count);
        $this->assertEquals([
            'forComposerLoadedFiles',
            'forRoutes',
            PHP_EOL.'forBladeFiles',
        ], $iterator->print);

        (CheckDD::$onErrorCallback)(PhpFileDescriptor::make(__DIR__.'/dd-init.stub'), [0, 'dd', 32]);

        $error = ErrorPrinter::singleton()->errorsList['ddFound'][0];
        $this->assertEquals(__DIR__.'/dd-init.stub', $error->getLinkPath());
    }
}
