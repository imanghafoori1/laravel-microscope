<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckExtraSemiColons;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Features\SearchReplace\Commands\CheckExtraSemiColons;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\SearchReplace\PatternRefactorings;
use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\Iterator;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use Imanghafoori\LaravelMicroscope\Tests\SampleComposerJson;
use Imanghafoori\LaravelMicroscope\Tests\SamplePrinter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CheckExtraSemicolonsTest extends TestCase
{
    public function setUp(): void
    {
        BasePath::$path = __DIR__;
        mkdir(__DIR__.'/app');
        copy(__DIR__.'/extra-semi-initial.stub', __DIR__.'/app/ExtraSemi.php');

        $_SESSION['printAll'] = [];
        $_SESSION['writeln'] = [];
        $_SESSION['confirm'] = [];
    }

    public function tearDown(): void
    {
        unset($_SESSION['printAll']);
        unset($_SESSION['writeln']);
        unset($_SESSION['confirm']);
        unlink(__DIR__.'/app/ExtraSemi.php');
        rmdir(__DIR__.'/app');

        BasePath::$path = null;
    }

    #[Test]
    public function extra_semicolons_get_removed()
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
        $helpers = new CheckExtraSemiColons();
        $helpers->options = new class
        {
            public function option()
            {
                return '';
            }
        };

        ErrorPrinter::singleton()->printer = new class extends SamplePrinter {
            //
        };
        $helpers->errorPrinter = ErrorPrinter::singleton();
        $helpers->handleCommand($iterator);

        $actual = file_get_contents(__DIR__.'/app/ExtraSemi.php');
        $expected = file_get_contents(__DIR__.'/extra-semi-final.stub');

        $this->assertEquals($expected, $actual);
    }
}
