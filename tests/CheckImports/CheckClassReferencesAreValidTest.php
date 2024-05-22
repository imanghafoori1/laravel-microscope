<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckImports;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Tests\CheckImports\MockExistenceChecker\AlwaysExistsMock;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;
use PHPUnit\Framework\TestCase;

class CheckClassReferencesAreValidTest extends TestCase
{
    /** @test */
    public function check()
    {
        $absPath = __DIR__.'/wrongImport.stub';
        $file = PhpFileDescriptor::make($absPath);
        $tokens = token_get_all(file_get_contents($absPath));
        CheckClassReferencesAreValid::$extraCorrectImportsHandler = MockHandlers\MockExtraImportsHandler::class;
        CheckClassReferencesAreValid::$extraWrongImportsHandler = MockHandlers\MockerUnusedWrongImportsHandler::class;
        CheckClassReferencesAreValid::$wrongClassRefsHandler = MockHandlers\MockWrongClassRefsHandler::class;

        ImportsAnalyzer::$existenceChecker = AlwaysExistsMock::class;

        CheckClassReferencesAreValid::check($file, (function ($tokens) {
            $imports = ParseUseStatement::parseUseStatements($tokens);

            return $imports[0] ?: [$imports[1]];
        })($tokens));

        $extraImportHandler = MockHandlers\MockExtraImportsHandler::$calls;
        $unusedWrongImportsHandler = MockHandlers\MockerUnusedWrongImportsHandler::$calls;
        $wrongClassRefsHandler = MockHandlers\MockWrongClassRefsHandler::$calls;

        $this->assertEquals([
            [
                [
                    'doo' => ['doo', 5],
                    'Foooo' => ['Foooo', 6],
                ],
                __DIR__.'/wrongImport.stub',
            ],
        ], $extraImportHandler);

        $this->assertEquals([[
            0 => [],
            1 => __DIR__.'/wrongImport.stub',
        ]], $unusedWrongImportsHandler);

        $this->assertEquals([[
            0 => [],
            1 => __DIR__.'/wrongImport.stub',
        ]], $wrongClassRefsHandler);
    }
}
