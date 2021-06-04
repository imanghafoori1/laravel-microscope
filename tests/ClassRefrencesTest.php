<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Analyzers\ClassReferenceFinder;

class ClassRefrencesTest extends BaseTestClass
{
    /** @test */
    public function can_extract_namespace()
    {
        $string = file_get_contents(__DIR__.'/stubs/class_refrences.php');
        $tokens = token_get_all($string);

        $output = array_values(ClassReferenceFinder::process($tokens));

        $this->assertEquals([
            [391, 'namespace', 3],
            [311, 'Imanghafoori', 3],
            [393, '\\', 3],
            [311, 'LaravelMicroscope', 3],
            [393, '\\', 3],
            [311, 'FileReaders', 3],
        ], $output[0]);

        $this->assertEquals([[311, 'InterF1', 15]], $output[1]);
        $this->assertEquals([[311, 'InterF2', 15]], $output[2]);
        $this->assertEquals([[311, 'Trait1', 16]], $output[3]);
        $this->assertEquals([[311, 'Trait2', 16]], $output[4]);
        $this->assertEquals([[311, 'Trait3', 16]], $output[5]);

        $this->assertEquals([[311, 'TypeHint1', 17]], $output[3+3]);
        $this->assertEquals([[311, 'TypeHint2', 17]], $output[4+3]);
        $this->assertEquals([[311, 'Finder', 23]], $output[5+3]);
        $this->assertEquals([[311, 'DirectoryNotFoundException', 31]], $output[6+3]);
        //$this->assertEquals([[311, 'Finder', 36]], $output[7+3]);
        //$this->assertEquals([[393, '\\', 37], [311, 'Exception', 37],], $output[8+3]);
        //$this->assertEquals([[393, '\\', 37], [311, 'ErrorException', 37]], $output[9+3]);
        //$this->assertEquals([[311, 'MyAmIClass', 41]], $output[10+3]);
        //$this->assertEquals([[393, '\\', 42,], [311, 'YetAnotherclass', 42]], $output[11+3]);
        //$this->assertEquals([[311, 'HalfImported', 43], [393, '\\', 43,], [311, 'TheRest', 43],], $output[12+3]);
        //$this->assertEquals([[311, 'TypeHint1', 51]], $output[13+3]);
        //$this->assertEquals([[311, "Newed", 59]], $output[14+3]);
        //$this->assertEquals([[ 311, "A", 60 ], [ 393, "\\", 60 ], [ 311, "Newed", 60 ]], $output[15+3]);
    }
}
