<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\TokenAnalyzer\ClassReferenceFinder;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

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
            'DirectoryNotFoundException' => ["Symfony\Component\Finder\Exception\DirectoryNotFoundException", 6],
            'Action' => ["Imanghafoori\LaravelMicroscope\Checks\ActionsComments", 5],
            'Finder' => ["Symfony\Component\Finder\Symfony\Component\Finder\Finder", 6],
            'Closure' => ['Closure', 11],
            'PasswordBroker' => ["Illuminate\Contracts\Auth\PasswordBroker", 10],
            'HalfImported' =>  ["Illuminate\Contracts\HalfImported", 12],

        ];

        $this->assertEquals($expected, $uses);
    }

    /** @test */
    public function can_skip_imported_global_functions()
    {
        $tokens = $this->getTokens('/stubs/auth.stub');

        [$result, $uses] = ParseUseStatement::parseUseStatements($tokens);

        $this->assertEquals([], $uses);
        $this->assertEquals([], $result);

        $this->assertEquals([[], ''], ClassReferenceFinder::process($tokens));
    }
}
