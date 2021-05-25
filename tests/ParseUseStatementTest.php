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

        ];

        $this->assertEquals($expected, $uses);
    }

    /** @test */
    public function can_find_class_references()
    {
        $tokens = $this->getTokens('/stubs/group_import.stub');

        $classes = ParseUseStatement::findClassReferences($tokens, '');
        $classes = array_values($classes);

        $this->assertEquals([
            'class' => 'Imanghafoori\LaravelMicroscope\FileReaders\A\Hello',
            'line' => 13,
            'namespace' => "Imanghafoori\LaravelMicroscope\FileReaders",
        ], $classes[0]);

        $this->assertEquals([
            'class' => "Symfony\Component\Finder\Symfony\Component\Finder\Finder",
            'line' => 21,
            'namespace' => "Imanghafoori\LaravelMicroscope\FileReaders",
        ], $classes[1]);

        $this->assertEquals([
            'class' => "Symfony\Component\Finder\Exception\DirectoryNotFoundException",
            'line' => 29,
            'namespace' => "Imanghafoori\LaravelMicroscope\FileReaders",
        ], $classes[2]);

        $this->assertEquals([
            'class' => "Imanghafoori\LaravelMicroscope\FileReaders\MyAmIClass",
            'line' => 32,
            'namespace' => "Imanghafoori\LaravelMicroscope\FileReaders",
        ], $classes[3]);

        $this->assertEquals([
            'class' => "\YetAnotherclass",
            'line' => 33,
            'namespace' => "Imanghafoori\LaravelMicroscope\FileReaders",
        ], $classes[4]);
    }
}
