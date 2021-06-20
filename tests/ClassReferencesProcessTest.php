<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Analyzers\ClassReferenceFinder;

class ClassReferencesProcessTest extends BaseTestClass
{
    /** @test */
    public function can_detect_class_references()
    {
        $string = file_get_contents(__DIR__.'/stubs/class_references.stub');
        $tokens = token_get_all($string);

        $output = ClassReferenceFinder::process($tokens);
        $this->assertEquals([[311, 'InterF1', 9]], $output[1+1]);
        $this->assertEquals([[311, 'InterF2', 9]], $output[1+2]);

        $this->assertEquals([[311, 'Trait1', 11]], $output[1+4]);
        $this->assertEquals([[311, 'Trait2', 11]], $output[1+5]);
        $this->assertEquals([[311, 'Trait3', 13]], $output[1+6]);

        $this->assertEquals([[311, 'TypeHint1', 17]], $output[1+7]);
        $this->assertEquals([[311, 'TypeHint2', 17]], $output[1+8]);
        $this->assertEquals([[311, 'Finder', 23]], $output[1+9]);
        $this->assertEquals([[311, 'DirectoryNotFoundException', 31]], $output[1+10]);
        $this->assertEquals([[311, 'Finder', 36]], $output[1+11]);
        $this->assertEquals([[311, 'MyAmIClass', 41]], $output[1+14]);
        $this->assertEquals([[311, 'TypeHint1', 51]], $output[1+17]);
        $this->assertEquals([[311, 'ReturnyType2', 51]], $output[1+18]);
        $this->assertEquals([[311, 'Newed', 59]], $output[1+20]);
        $this->assertEquals([[311, 'self', 56]], $output[1+19]);

        if (version_compare(phpversion(), '8.0.0') !== 1) {
            $this->assertEquals([
                [391, 'namespace', 3],
                [311, 'Imanghafoori', 3],
                [393, '\\', 3],
                [311, 'LaravelMicroscope', 3],
                [393, '\\', 3],
                [311, 'FileReaders', 3],
            ], $output[0]);

            $this->assertEquals([[393, '\\', 9], [311, 'Inline', 9], [393, '\\', 9], [311, 'InterF3', 9]], $output[1+3]);
            $this->assertEquals([[393, '\\', 9], [311, 'A', 9], [393, '\\', 9], [311, 'ParentClass', 9]], $output[1]);
            $this->assertEquals([[393, '\\', 37], [311, 'Exception', 37]], $output[1+12]);
            $this->assertEquals([[393, '\\', 37], [311, 'ErrorException', 37]], $output[1+13]);
            $this->assertEquals([[393, '\\', 42], [311, 'YetAnotherclass', 42]], $output[1+15]);
            $this->assertEquals([[311, 'HalfImported', 43], [393, '\\', 43], [311, 'TheRest', 43]], $output[1+16]);
            $this->assertEquals([[311, 'A', 60], [393, '\\', 60], [311, 'Newed', 60]], $output[1+21]);
        }
    }
}
