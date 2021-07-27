<?php

namespace Imanghafoori\LaravelMicroscope\Tests\pattern_matching;

use Imanghafoori\LaravelMicroscope\Refactor\PatternParser;
use Imanghafoori\LaravelMicroscope\Tests\BaseTestClass;

class UntilTest extends BaseTestClass
{
    /** @test */
    public function until_placeholder()
    {
        $patterns = [
            'return response()"<until>";' => ['replace' => 'response()"<1>"->throwResponse();'],
        ];

        $startFile = file_get_contents(__DIR__.'/../stubs/SimplePostController.stub');
        $resultFile = file_get_contents(__DIR__.'/../stubs/SimplePostController2.stub');
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, token_get_all($startFile));

        $this->assertEquals($resultFile, $newVersion);

        $this->assertEquals([17, 24], $replacedAt);
    }

    /** @test */
    public function until_matching()
    {
        $patterns = [
            "if('<until_match>'){}" => ['replace' => 'if(true) {"<1>";}'],
        ];

        $startFile = "<?php if(foo()->bar()) {}";
        $resultFile = "<?php if(true) {foo()->bar();}";

        $tokens = token_get_all($startFile);
        [$newVersion, $replacedAt] = PatternParser::searchReplace($patterns, $tokens);

        $this->assertEquals($resultFile, $newVersion);

        $this->assertEquals([1], $replacedAt);
    }
}
