<?php

namespace Imanghafoori\LaravelMicroscope\Tests\EnforceHelpers;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Commands\EnforceHelpers;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\Iterator;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use Imanghafoori\LaravelMicroscope\SearchReplace\PatternRefactorings;
use PHPUnit\Framework\TestCase;

class EnforceHelpersTest extends TestCase
{
    public function setUp(): void
    {
        BasePath::$path = __DIR__;
        mkdir(__DIR__.'/app');
        copy(__DIR__.'/MyClass.stub', __DIR__.'/app/MyClass.php');

        $_SESSION['printAll'] = [];
        $_SESSION['writeln'] = [];
        $_SESSION['confirm'] = [];
    }

    public function tearDown(): void
    {
        unset($_SESSION['printAll']);
        unset($_SESSION['writeln']);
        unset($_SESSION['confirm']);
        unlink(__DIR__.'/app/MyClass.php');
        rmdir(__DIR__.'/app');
    }

    public function testEnforce()
    {
        ForBladeFiles::$paths = [];
        ComposerJson::$composer = function () {
            return new class
            {
                public function readAutoload()
                {
                    return [
                        '/' => ['App\\' => 'app'],
                    ];
                }

                public function readAutoloadClassMap()
                {
                    return [
                        '/' => [],
                    ];
                }

                public function autoloadedFilesList()
                {
                    return [
                        '/' => [],
                    ];
                }
            };
        };

        $checkSet = CheckSet::init([PatternRefactorings::class]);
        $iterator = new Iterator($checkSet, new class
        {
            public function write($msg)
            {
                $_SESSION['printAll'][] = $msg;
            }
        });
        $helpers = new EnforceHelpers();
        ErrorPrinter::singleton()->printer = new class 
        {
            public function writeln($msg)
            {
                $_SESSION['writeln'][] = $msg;
            }  
            public function confirm($msg)
            {
                $_SESSION['confirm'][] = $msg;

                return true;
            }
        };
        $helpers->errorPrinter = ErrorPrinter::singleton();
        $helpers->handleCommand($iterator);

        $this->assertStringContainsString('<fg=blue>./composer.json</>', $_SESSION['printAll'][0]);
        $this->assertStringContainsString('PSR-4', $_SESSION['printAll'][1]);
        $this->assertStringContainsString('App\:', $_SESSION['printAll'][2]);
        $this->assertStringContainsString('./app', $_SESSION['printAll'][3]);
        $this->assertStringContainsString('(1 file)', $_SESSION['printAll'][4]);

        $this->assertEquals(
            file_get_contents(__DIR__.'/MyClass-expected.stub'),
            file_get_contents(__DIR__.'/app/MyClass.php')
        );
        $this->assertCount(4, $_SESSION['confirm']);
        $this->assertCount(16, $_SESSION['writeln']);
    }
}