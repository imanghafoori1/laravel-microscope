<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckImports;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\ExtraCorrectImports;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\ExtraWrongImports;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\FixWrongClassRefs;
use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use Imanghafoori\LaravelMicroscope\Tests\CheckImports\MockExistenceChecker\AlwaysExistsMock;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;
use PHPUnit\Framework\TestCase;

class CheckClassReferencesAreValidTest extends TestCase
{
    public function setUp(): void
    {
        CheckClassReferencesAreValid::$extraCorrectImportsHandler = MockHandlers\MockExtraImportsHandler::class;
        CheckClassReferencesAreValid::$extraWrongImportsHandler = MockHandlers\MockerUnusedWrongImportsHandler::class;
        CheckClassReferencesAreValid::$wrongClassRefsHandler = MockHandlers\MockWrongClassRefsHandler::class;

        ForBladeFiles::$paths = [
            'hint' => [],
        ];
        BasePath::$path = __DIR__;
    }

    public function tearDown(): void
    {
        CheckClassReferencesAreValid::$extraCorrectImportsHandler = ExtraCorrectImports::class;
        CheckClassReferencesAreValid::$extraWrongImportsHandler = ExtraWrongImports::class;
        CheckClassReferencesAreValid::$wrongClassRefsHandler = FixWrongClassRefs::class;

        ForBladeFiles::$paths = [];
        BasePath::$path = null;
    }

    public function test_check()
    {
        $absPath = __DIR__.'/wrongImport.stub';
        $file = PhpFileDescriptor::make($absPath);

        ImportsAnalyzer::$existenceChecker = AlwaysExistsMock::class;

        CheckClassReferencesAreValid::$imports = function (PhpFileDescriptor $file) {
            $imports = ParseUseStatement::parseUseStatements($file->getTokens());

            return $imports[0] ?: [$imports[1]];
        };
        CheckClassReferencesAreValid::check($file);

        $extraImportHandler = MockHandlers\MockExtraImportsHandler::$calls;
        $unusedWrongImportsHandler = MockHandlers\MockerUnusedWrongImportsHandler::$calls;
        $wrongClassRefsHandler = MockHandlers\MockWrongClassRefsHandler::$calls;

        $this->assertEquals(['doo' => ['doo', 5], 'Foooo' => ['Foooo', 6]], $extraImportHandler[0][0]);
        $this->assertEquals(__DIR__.'/wrongImport.stub', $extraImportHandler[0][1]->getAbsolutePath());

        $this->assertEquals([], $unusedWrongImportsHandler[0][0]);
        $this->assertEquals(__DIR__.'/wrongImport.stub', $unusedWrongImportsHandler[0][1]->getAbsolutePath());

        $this->assertEquals([], $wrongClassRefsHandler);
    }
}
