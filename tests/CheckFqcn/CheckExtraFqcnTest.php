<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckFqcn;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckExtraFQCN\CheckExtraFQCNCommand;
use Imanghafoori\LaravelMicroscope\Foundations\Iterator;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use PHPUnit\Framework\TestCase;

class CheckExtraFqcnTest extends TestCase
{
    public function setUp(): void
    {
        touch(__DIR__.'/composer.json');
        file_put_contents(__DIR__.'/composer.json', '{}');
        copy(__DIR__.'/fqcn-initial.stub', __DIR__.'/test.php');
    }

    public function tearDown(): void
    {
        unlink(__DIR__.'/composer.json');
        unlink(__DIR__.'/test.php');
    }

    public function testCommand()
    {
        ComposerJson::$composer = function () {
            return \ImanGhafoori\ComposerJson\ComposerJson::make(__DIR__);
        };

        $command = new CheckExtraFQCNCommand();
        $command->options = new class
        {
            public function option()
            {
                return '';
            }
        };

        $command->errorPrinter = ErrorPrinter::singleton();

        $iterator = new Iterator(CheckSet::init(new class
        {
            public static function handle(PhpFileDescriptor $file)
            {
            }
        }), new class
        {
            public function write($msg)
            {
                //
            }
        });

        $command->handleCommand($iterator, new class
        {
            public function line($msg)
            {
                //
            }
        });

        $this->assertTrue(true);
    }
}
