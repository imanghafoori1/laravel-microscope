<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckPsr4;

use ImanGhafoori\ComposerJson\ComposerJson as Composer;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Features\Psr4\Console\CheckPsr4ArtisanCommand;
use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use PHPUnit\Framework\TestCase;

class CheckPsr4Test extends TestCase
{
    public function setUp(): void
    {
        $_SERVER['write'] = [];
        BasePath::$path = __DIR__;
        copy($this->initFile(), $this->tempFile());
    }

    public function tearDown(): void
    {
        unset($_SERVER['write']);
        unlink($this->tempFile());
        BasePath::$path = null;
    }

    public function testCheckPsr4()
    {
        ForBladeFiles::$paths = [
            'hint' => [],
        ];
        $_SERVER['argv'][1] = 'check:psr4';
        ComposerJson::$composer = fn () => Composer::make(__DIR__);
        $command = new CheckPsr4ArtisanCommand();
        $command->input(new class
        {
            public function getOption()
            {
                return '';
            }
        });
        $command->output(new class{
            public function writeln($msg)
            {
                $_SERVER['write'][] = $msg;
            }

            public function write($msg)
            {
                //
            }

            public function confirm($msg)
            {
                return true;
            }

            public function getFormatter()
            {
                return new class{
                    public function hasStyle()
                    {
                        return true;
                    }
                };
            }
        });
        $command->handle();

        $actual = file_get_contents($this->tempFile());
        $expected = file_get_contents($this->expectedFile());

        $this->assertEquals($expected, $actual);
    }

    private function tempFile(): string
    {
        return __DIR__.'/app/test.php';
    }

    private function initFile(): string
    {
        return __DIR__.'/app/MyClass-init.stub';
    }

    private function expectedFile(): string
    {
        return __DIR__.'/app/MyClass-final.stub';
    }
}