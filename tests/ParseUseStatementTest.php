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
    public function can_find_class_references()
    {
        $tokens = $this->getTokens('/stubs/group_import.stub');

        [$classes, $namespace] = ParseUseStatement::findClassReferences($tokens, '');
        $this->assertEquals("Imanghafoori\LaravelMicroscope\FileReaders", $namespace);

        $this->assertEquals([
            'class' => 'Imanghafoori\LaravelMicroscope\FileReaders\A\Hello',
            "line" => 14,
        ], $classes[0]);

        $this->assertEquals([
            "class" => "Symfony\Component\Finder\Symfony\Component\Finder\Finder",
            "line" => 22,
        ], $classes[1]);

        $this->assertEquals([
            "class" => "Symfony\Component\Finder\Exception\DirectoryNotFoundException",
            "line" => 30,
        ], $classes[2]);

        $this->assertEquals([
            "class" => "Imanghafoori\LaravelMicroscope\FileReaders\MyAmIClass",
            "line" => 33,
        ], $classes[3]);

        $this->assertEquals([
            "class" => "\YetAnotherclass",
            "line" => 34,
        ], $classes[4]);

        $this->assertEquals([
            "class" => "Illuminate\Contracts\HalfImported\TheRest",
            "line" => 35,
        ], $classes[5]);
    }
}
