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
        $tokens = $this->getTokens('/stubs/class_refrences.php');

        [$classes, $namespace] = ParseUseStatement::findClassReferences($tokens, 'class_refrences.stub');
        $this->assertEquals("Imanghafoori\LaravelMicroscope\FileReaders", $namespace);

        $this->assertEquals([
            'class' => 'Imanghafoori\LaravelMicroscope\FileReaders\InterF1',
            'line' => 9,
        ], $classes[0]);

        $this->assertEquals([
            'class' => 'Imanghafoori\LaravelMicroscope\FileReaders\InterF2',
            'line' => 9,
        ], $classes[1]);

         $this->assertEquals([
             'class' => 'Imanghafoori\LaravelMicroscope\FileReaders\Trait1',
             'line' => 11
         ], $classes[2]);

         $this->assertEquals([
             'class' => 'Imanghafoori\LaravelMicroscope\FileReaders\Trait2',
             'line' => 11
         ], $classes[3]);

         $this->assertEquals([
             'class' => 'Imanghafoori\LaravelMicroscope\FileReaders\Trait3',
             'line' => 13
         ], $classes[4]);

        $this->assertEquals([
            'class' => "Imanghafoori\LaravelMicroscope\FileReaders\TypeHint1",
            'line' => 17,
        ], $classes[5]);
        $this->assertEquals([
            'class' => "Imanghafoori\LaravelMicroscope\FileReaders\TypeHint2",
            'line' => 17,
        ], $classes[6]);

        $this->assertEquals([
            'class' => "Symfony\Component\Finder\Symfony\Component\Finder\Finder",
            'line' => 23,
        ], $classes[7]);


        $this->assertEquals([
            'class' => "Symfony\Component\Finder\Exception\DirectoryNotFoundException",
            'line' => 31,
        ], $classes[8]);

        $this->assertEquals([
            'class' => "Symfony\Component\Finder\Symfony\Component\Finder\Finder",
            'line' => 36,
        ], $classes[9]);

        $this->assertEquals([
            'class' => "\Exception",
            'line' => 37,
        ], $classes[10]);

        $this->assertEquals([
            'class' => "\ErrorException",
            'line' => 37,
        ], $classes[11]);

      $this->assertEquals([
            'class' => "Imanghafoori\LaravelMicroscope\FileReaders\MyAmIClass",
            'line' => 41,
        ], $classes[12]);

      $this->assertEquals([
            'class' => "\YetAnotherclass",
            'line' => 42,
        ], $classes[13]);

      $this->assertEquals([
          'class' => 'Illuminate\Contracts\HalfImported\TheRest',
          'line' => 43
      ], $classes[14]);

      $this->assertEquals([
          'class' => 'Imanghafoori\LaravelMicroscope\FileReaders\TypeHint1',
          'line' => 51
      ], $classes[15]);

      $this->assertEquals([
          'class' => 'Imanghafoori\LaravelMicroscope\FileReaders\Newed',
          'line' => 59
      ], $classes[16]);

      $this->assertEquals([
          'class' => 'Imanghafoori\LaravelMicroscope\FileReaders\A\Newed',
          'line' => 60
      ], $classes[17]);
    }
}
