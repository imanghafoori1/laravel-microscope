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
    public function test_check()
    {
        $absPath = __DIR__.'/wrongImport.stub';
        $file = PhpFileDescriptor::make($absPath);
        CheckClassReferencesAreValid::$extraWrongImportsHandler = MockHandlers\MockerUnusedWrongImportsHandler::class;
        CheckClassReferencesAreValid::$wrongClassRefsHandler = MockHandlers\MockWrongClassRefsHandler::class;

        ImportsAnalyzer::$existenceChecker = AlwaysExistsMock::class;

        CheckClassReferencesAreValid::$importsProvider = function (PhpFileDescriptor $file) {
            $imports = ParseUseStatement::parseUseStatements($file->getTokens());

            return $imports[0] ?: [$imports[1]];
        };
        CheckClassReferencesAreValid::check($file);

        $unusedWrongImportsHandler = MockHandlers\MockerUnusedWrongImportsHandler::$calls;
        $wrongClassRefsHandler = MockHandlers\MockWrongClassRefsHandler::$calls;

        $this->assertEquals([], $unusedWrongImportsHandler[0][0]);
        $this->assertEquals(__DIR__.'/wrongImport.stub', $unusedWrongImportsHandler[0][1]->getAbsolutePath());

        $this->assertEquals([], $wrongClassRefsHandler);
    }
}
