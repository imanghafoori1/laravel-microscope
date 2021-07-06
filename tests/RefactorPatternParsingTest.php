<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Analyzers\PatternParser;

class RefactorPatternParsingTest extends BaseTestClass
{
    /** @test */
    public function can_parse_patterns()
    {
        $patterns = require __DIR__.'/stubs/refactor_patterns.php';
        $sampleFileTokens =  token_get_all(file_get_contents(__DIR__.'/stubs/SimplePostController.stub'));


        $matches = PatternParser::search($patterns, $sampleFileTokens);

        $this->assertEquals($matches[0][0], [['start' => 87, 'end' =>126], [[315, "'hi'", 18]]]);
        $this->assertEquals($matches[0][1], [['start' => 151, 'end' => 184], [[315, "'Hello'", 24]]]);
    }
}
