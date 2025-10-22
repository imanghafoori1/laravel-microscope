<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckImports;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\CheckImportsCommand;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\ExtraCorrectImports;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\ExtraWrongImports;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\FixWrongClassRefs;
use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\Iterator;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;
use PHPUnit\Framework\TestCase;

class CheckImportCommandTest extends TestCase
{
    public function setUp(): void
    {
        CheckClassReferencesAreValid::$extraCorrectImportsHandler = ExtraCorrectImports::class;
        CheckClassReferencesAreValid::$extraWrongImportsHandler = ExtraWrongImports::class;
        CheckClassReferencesAreValid::$wrongClassRefsHandler = FixWrongClassRefs::class;

        ForBladeFiles::$paths = [
            'hint' => [],
        ];
        BasePath::$path = __DIR__;
        copy(__DIR__.'/wrongImport.stub', __DIR__.'/app/test.php');
        copy(__DIR__.'/app/Sample-imports.stub', __DIR__.'/app/Hello.php');
    }

    public function tearDown(): void
    {
        ForBladeFiles::$paths = [];
        unlink(__DIR__.'/app/test.php');
        unlink(__DIR__.'/app/Hello.php');
        BasePath::$path = null;
    }

    public function test_command()
    {
        $command = new CheckImportsCommand();
        ComposerJson::$composer = function () {
            return \ImanGhafoori\ComposerJson\ComposerJson::make(__DIR__);
        };

        ImportsAnalyzer::$existenceChecker = new class
        {
            public static function check($dump)
            {
                return false;
            }
        };
        $iterator = new Iterator(CheckSet::init([
            CheckClassReferencesAreValid::class,
        ], PathFilterDTO::make()), new class
        {
            public function write()
            {
            }
        });
        $command->handleCommand($iterator, new class
        {
            public function option()
            {
                return '';
            }

            public function line()
            {
                //
            }
        });

        $this->assertTrue(true);
    }
}
