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

        $this->assertEquals($matches[0][0], [['start' => 87, 'end' =>126],
            [
                [T_VARIABLE, '$user', 15],
                [T_CONSTANT_ENCAPSED_STRING, "'hi'", 18]
            ]
        ]);
        $this->assertEquals($matches[0][1], [['start' => 151, 'end' => 184], [
            [T_VARIABLE, '$club', 23],
            [T_CONSTANT_ENCAPSED_STRING, "'Hello'", 24]
        ]]);
    }
}
