<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckClassyStrings;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckClassyStrings\ClassifyStringsCommand;
use Imanghafoori\LaravelMicroscope\Foundations\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Foundations\Reports\LineSeperator;
use PHPUnit\Framework\TestCase;

class CheckClassyStringTest extends TestCase
{
    public function setUp(): void
    {
        BasePath::$path = __DIR__;
        copy(__DIR__.DIRECTORY_SEPARATOR.'claasy_string.stub', __DIR__.DIRECTORY_SEPARATOR.'claasy_string.php');
        $_SERVER['msg'] = [];
    }

    public function tearDown(): void
    {
        BasePath::$path = '';
        unlink(__DIR__.DIRECTORY_SEPARATOR.'claasy_string.php');
        unset($_SERVER['msg']);
    }

    public function test_command()
    {
        LineSeperator::$color = 'white';

        ErrorPrinter::singleton()->printer = new class
        {
            public function writeln()
            {
                //
            }

            public function text()
            {
                //
            }

            public function confirm()
            {
                return true;
            }
        };
        ComposerJson::$composer = function () {
            return new class
            {
                public function readAutoload()
                {
                    return [
                        '/' => [
                            'App\\' => 'app',
                            'Imanghafoori\LaravelMicroscope\Tests' => 'tests',
                        ],
                    ];
                }

                public function getRelativePathFromNamespace()
                {
                    return 'app/Models/User.php';
                }

                public function getNamespacedClassFromPath()
                {
                    return '';
                }
            };
        };

        $command = new ClassifyStringsCommand();
        $command->handleCommand(new class
        {
            public function forComposerLoadedFiles()
            {
                return 'forComposerLoadedFiles';
            }

            public function forRoutes()
            {
                return 'forRoutes';
            }

            public function forBladeFiles()
            {
                return 'forBladeFiles';
            }

            public function printAll($messages)
            {
                $_SERVER['msg'][] = $messages;
            }
        }, new class
        {
            public function info()
            {
                //
            }
        });

        $this->assertCount(1, $_SERVER['msg']);
        $this->assertIsArray($_SERVER['msg'][0]);
        $this->assertEquals($_SERVER['msg'][0], [
            'forComposerLoadedFiles',
            'forRoutes',
            PHP_EOL.'forBladeFiles',
        ]);

        $check = $command->checks[0];

        $check::check(PhpFileDescriptor::make(__DIR__.DIRECTORY_SEPARATOR.'claasy_string.php'));
        $this->assertEquals(
            file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'claasy_string.php'),
            file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'claasy_string-expected.stub'),
        );
    }
}
