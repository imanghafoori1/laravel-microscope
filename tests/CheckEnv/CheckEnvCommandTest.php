<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckEndif;

use Imanghafoori\LaravelMicroscope\Features\CheckEnvCalls\CheckEnvCallsCommand;
use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CheckEnvCommandTest extends TestCase
{
    public function setUp(): void
    {
        BasePath::$path = __DIR__;
    }

    public function tearDown(): void
    {
        BasePath::$path = '';
    }

    #[Test]
    public function basic_command()
    {
        LaravelPaths::$migrationDirs = function () {
            return yield from [__DIR__.DIRECTORY_SEPARATOR.'migrations'];
        };

        $command = new CheckEnvCallsCommand();
        $command->output(new class
        {
            public $msg = [];

            public function writeln($msg)
            {
                $this->msg[] = $msg;
            }

            public function write($msg)
            {
                $this->msg[] = $msg;
            }
        });

        $command->input(new class
        {
            public function getOption()
            {
                return '';
            }
        });

        LaravelPaths::$configPath = [];

        $iterator = new class
        {
            public $msg;

            public function forComposerLoadedFiles()
            {
                return '1';
            }

            public function forBladeFiles()
            {
                return '2';
            }

            public function forRoutes()
            {
                return '3';
            }

            public function printAll($msg)
            {
                $this->msg = $msg;
            }
        };
        $command->handleCommand($iterator);
        $this->assertIsArray($iterator->msg);
        $this->assertCount(3, $iterator->msg);
        $this->assertEquals(1, $iterator->msg[0]);
        $this->assertEquals(2, $iterator->msg[1]);
        $this->assertEquals(3, $iterator->msg[2]);
    }
}
