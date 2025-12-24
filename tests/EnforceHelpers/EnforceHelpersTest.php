<?php

namespace Imanghafoori\LaravelMicroscope\Tests\EnforceHelpers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\SearchReplace\Commands\EnforceHelpers;
use Imanghafoori\LaravelMicroscope\Features\SearchReplace\PatternRefactorings;
use Imanghafoori\LaravelMicroscope\Foundations\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\Iterator;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use Imanghafoori\LaravelMicroscope\Tests\SampleComposerJson;
use Imanghafoori\LaravelMicroscope\Tests\SamplePrinter;
use PHPUnit\Framework\TestCase;

class EnforceHelpersTest extends TestCase
{
    public function setUp(): void
    {
        ErrorPrinter::$instance = null;
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
            return new class extends SampleComposerJson {
                //
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
        ErrorPrinter::singleton()->printer = new class extends SamplePrinter {
            //
        };

        $helpers = new EnforceHelpers();
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
