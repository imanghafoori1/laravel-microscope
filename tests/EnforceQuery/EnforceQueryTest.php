<?php

namespace Imanghafoori\LaravelMicroscope\Tests\EnforceQuery;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Commands\EnforceQuery;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\Iterator;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use Imanghafoori\LaravelMicroscope\SearchReplace\PatternRefactorings;
use Imanghafoori\LaravelMicroscope\Tests\SampleComposerJson;
use Imanghafoori\LaravelMicroscope\Tests\SamplePrinter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EnforceQueryTest extends TestCase
{
    public function setUp(): void
    {
        BasePath::$path = __DIR__;
        mkdir(__DIR__.'/app');
        copy(__DIR__.'/enforce-query-init.stub', __DIR__.'/app/EnforceQuery.php');

        $_SESSION['printAll'] = [];
        $_SESSION['writeln'] = [];
        $_SESSION['confirm'] = [];
    }

    public function tearDown(): void
    {
        unset($_SESSION['printAll']);
        unset($_SESSION['writeln']);
        unset($_SESSION['confirm']);
        unlink(__DIR__.'/app/EnforceQuery.php');
        rmdir(__DIR__.'/app');

        BasePath::$path = null;
    }

    #[Test]
    public function extra_dynamic_where_get_refactored()
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
        $helpers = new EnforceQuery();
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

        $actual = file_get_contents(__DIR__.'/app/EnforceQuery.php');
        $expected = file_get_contents(__DIR__.'/enforce-query-final.stub');

        $this->assertEquals($expected, $actual);
    }
}
