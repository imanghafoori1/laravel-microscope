<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Refactor\PatternParser;

class RefactorPatternParsingTest extends BaseTestClass
{
    /** @test */
    public function until_placeholder()
    {
        $patterns = [
            'return response()"<until>";' => 'response()"<1>"->throwResponse();',
        ];

        $startFile = file_get_contents(__DIR__.'/stubs/SimplePostController.stub');
        $resultFile = file_get_contents(__DIR__.'/stubs/SimplePostController2.stub');
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);

        $this->assertEquals([17, 24], $replacedAt);
    }

    /** @test */
    public function can_parse_patterns2()
    {
        $patterns = [
            "if (!'<variable>' && '<boolean>') { return response()->'<name>'(['message' => __('<string>')], '<number>'); }" => 'Foo::bar("<1>", "<2>", "<3>"(), "<4>");',
            'foo(false, true, null);' => 'bar("hi");',
        ];
        $startFile = file_get_contents(__DIR__.'/stubs/SimplePostController.stub');
        $resultFile = file_get_contents(__DIR__.'/stubs/ResultSimplePostController.stub');
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);
        $this->assertEquals([15, 23, 26, 27], $replacedAt);
    }

    /** @test */
    public function white_space()
    {
        $patterns = [
            "use App\Club;'<white_space>'use App\Events\MemberCommentedClubPost;" => "use App\Club; use App\Events\MemberCommentedClubPost;",

            "use Illuminate\Http\Request;'<white_space>'" => '',
        ];
        $startFile = file_get_contents(__DIR__.'/stubs/SimplePostController.stub');

        $resultFile = file_get_contents(__DIR__.'/stubs/EolSimplePostControllerResult.stub');
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);
        $this->assertEquals([5, 1, 8], $replacedAt);
    }

    /** @test */
    public function white_space_placeholder()
    {
        $patterns = [
            ")'<white_space>'{" => "){",
        ];
        $startFile = file_get_contents(__DIR__.'/stubs/SimplePostController.stub');

        $resultFile = file_get_contents(__DIR__.'/stubs/NoWhiteSpaceSimplePostController.stub');
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);
        $this->assertEquals([13, 15, 21,], $replacedAt);
    }

    /** @test */
    public function optional_white_space_placeholder()
    {
        $patterns = [
            "response()'<white_space>?'->json" => 'response()"<1>"->mson',
        ];
        $startFile = file_get_contents(__DIR__.'/stubs/SimplePostController.stub');

        $resultFile = file_get_contents(__DIR__.'/stubs/OptionalWhiteSpaceSimplePostController.stub');
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);
        $this->assertEquals([17, 24,], $replacedAt);
    }

    /** @test */
    public function until_matching()
    {
        $patterns = [
            "if('<until_match>'){}" => 'if(true) {"<1>"}',
        ];

        $startFile = "<?php if(foo()->bar()) {}";
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startFile));

        $resultFile = "<?php if(true) {foo()->bar()}";
        $this->assertEquals($resultFile, $newVersion);

        $this->assertEquals([1], $replacedAt);
    }

    /** @test */
    public function can_parse_patterns()
    {
        $patterns = require __DIR__.'/stubs/refactor_patterns.php';
        $sampleFileTokens = token_get_all(file_get_contents(__DIR__.'/stubs/SimplePostController.stub'));

        $matches = PatternParser::search($patterns, $sampleFileTokens);

        $this->assertEquals($matches[0][0]['values'],
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

        $this->assertEquals($matches[0][1]['values'],
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
