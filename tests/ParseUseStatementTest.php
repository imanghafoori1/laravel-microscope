<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Analyzers\ParseUseStatement;

class ParseUseStatementTest extends BaseTestClass
{
    /** @test */
    public function can_extract_imports()
    {
        $tokens = $this->getTokens('/stubs/interface_sample.stub');
        [$result, $uses] = ParseUseStatement::parseUseStatements($tokens);

        $expected = [
            'IncompleteTest' => ["PHPUnit\Framework\IncompleteTest", 3],
            'Countable' => ['Countable', 4],
        ];

        $this->assertEquals($expected, $uses);
        $this->assertEquals($expected, $result['interface_sample']);
    }

    /** @test */
    public function can_detect_group_imports()
    {
        $tokens = $this->getTokens('/stubs/group_import.stub');

        [$result, $uses] = ParseUseStatement::parseUseStatements($tokens);

        $expected = [
            'DirectoryNotFoundException' => ["Symfony\Component\Finder\Exception\DirectoryNotFoundException", 5],
            'Finder' => ["Symfony\Component\Finder\Symfony\Component\Finder\Finder", 5],
        ];

        $this->assertEquals($expected, $uses);
    }
}
