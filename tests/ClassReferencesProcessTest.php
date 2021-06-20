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

        [$output, $namespace] = ClassReferenceFinder::process($tokens);

        $this->assertEquals([[311, 'InterF1', 9]], $output[1]);
        $this->assertEquals([[311, 'InterF2', 9]], $output[2]);

        $this->assertEquals([[311, 'Trait1', 11]], $output[4]);
        $this->assertEquals([[311, 'Trait2', 11]], $output[5]);
        $this->assertEquals([[311, 'Trait3', 13]], $output[6]);

        $this->assertEquals([[311, 'TypeHint1', 17]], $output[7]);
        $this->assertEquals([[311, 'TypeHint2', 17]], $output[8]);
        $this->assertEquals([[311, 'Finder', 23]], $output[9]);
        $this->assertEquals([[311, 'DirectoryNotFoundException', 31]], $output[10]);
        $this->assertEquals([[311, 'Finder', 36]], $output[11]);
        $this->assertEquals([[311, 'MyAmIClass', 41]], $output[14]);
        $this->assertEquals([[311, 'TypeHint1', 51]], $output[17]);
        $this->assertEquals([[311, 'ReturnyType2', 51]], $output[18]);
        $this->assertEquals([[311, 'Newed', 59]], $output[20]);
        $this->assertEquals([[311, 'self', 56]], $output[19]);

        $this->assertEquals("Imanghafoori\LaravelMicroscope\FileReaders", $namespace);
        if (version_compare(phpversion(), '8.0.0') !== 1) {
            $this->assertEquals([[393, '\\', 9], [311, 'Inline', 9], [393, '\\', 9], [311, 'InterF3', 9]], $output[3]);

            $this->assertEquals([[393, '\\', 9], [311, 'A', 9], [393, '\\', 9], [311, 'ParentClass', 9]], $output[0]);

            $this->assertEquals([[393, '\\', 37], [311, 'Exception', 37]], $output[12]);
            $this->assertEquals([[393, '\\', 37], [311, 'ErrorException', 37]], $output[13]);
            $this->assertEquals([[393, '\\', 42], [311, 'YetAnotherclass', 42]], $output[15]);
            $this->assertEquals([[311, 'HalfImported', 43], [393, '\\', 43], [311, 'TheRest', 43]], $output[16]);
            $this->assertEquals([[311, 'A', 60], [393, '\\', 60], [311, 'Newed', 60]], $output[21]);
        }
    }
}
