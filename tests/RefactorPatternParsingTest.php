<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Refactor\PatternParser;

class RefactorPatternParsingTest extends BaseTestClass
{
    /** @test */
    public function can_parse_patterns()
    {
        $patterns = require __DIR__.'/stubs/refactor_patterns.php';
        $sampleFileTokens =  token_get_all(file_get_contents(__DIR__.'/stubs/SimplePostController.stub'));


        $matches = PatternParser::search($patterns, $sampleFileTokens);
        $this->assertEquals($matches[0][0][1],
            [
                [T_VARIABLE, '$user', 15],
                [T_STRING, 'true', 15],
                [T_STRING, 'json', 18],
                [T_CONSTANT_ENCAPSED_STRING, "'hi'", 18],
                [T_LNUMBER, 404, 18],
            ]
        );

        $start = $matches[0][0][0]['start'];
        $this->assertEquals($sampleFileTokens[$start][1], 'if');

        $end = $matches[0][0][0]['end'];
        $this->assertEquals($sampleFileTokens[$end], '}');

        $this->assertEquals($matches[0][1][1],
            [
                [T_VARIABLE, '$club', 23],
                [T_STRING, 'FALSE', 23],
                [T_STRING, 'json', 24],
                [T_CONSTANT_ENCAPSED_STRING, "'Hello'", 24],
                [T_LNUMBER, 403, 24],
            ]
        );

        $start = $matches[0][1][0]['start'];
        $this->assertEquals($sampleFileTokens[$start][1], 'if');

        $end = $matches[0][1][0]['end'];
        $this->assertEquals($sampleFileTokens[$end], '}');

        $start = $matches[1][0][0]['start'];
        $this->assertEquals($sampleFileTokens[$start][1], 'foo');

        $end = $matches[1][0][0]['end'];
        $this->assertEquals($sampleFileTokens[$end], ';');


        $start = $matches[1][1][0]['start'];
        $this->assertEquals($sampleFileTokens[$start][1], 'foo');

        $end = $matches[1][1][0]['end'];
        $this->assertEquals($sampleFileTokens[$end], ';');
    }
}
