<?php

namespace Imanghafoori\LaravelMicroscope\Tests\pattern_matching;

use Imanghafoori\LaravelMicroscope\Refactor\PatternParser;
use Imanghafoori\LaravelMicroscope\Tests\BaseTestClass;

class RefactorPatternParsingTest extends BaseTestClass
{
    /** @test */
    public function white_space()
    {
        $patterns = [
            "use App\Club;'<white_space>'use App\Events\MemberCommentedClubPost;" => "use App\Club; use App\Events\MemberCommentedClubPost;",

            "use Illuminate\Http\Request;'<white_space>'" => '',
        ];
        $startFile = file_get_contents(__DIR__.'/../stubs/SimplePostController.stub');

        $resultFile = file_get_contents(__DIR__.'/../stubs/EolSimplePostControllerResult.stub');
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
        $startFile = file_get_contents(__DIR__.'/../stubs/SimplePostController.stub');
        $resultFile = file_get_contents(__DIR__.'/../stubs/NoWhiteSpaceSimplePostController.stub');
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
        $startFile = file_get_contents(__DIR__.'/../stubs/SimplePostController.stub');

        $resultFile = file_get_contents(__DIR__.'/../stubs/OptionalWhiteSpaceSimplePostController.stub');
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);
        $this->assertEquals([17, 24,], $replacedAt);
    }
}
