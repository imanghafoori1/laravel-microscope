<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\TokenAnalyzer\FunctionCall;

class FunctionCallTest extends BaseTestClass
{
    /** @test */
    public function has_dd_test()
    {
     
        $tokens = token_get_all(file_get_contents(__DIR__ . "/stubs/function_test/some_function.sub"));

        $index = null;
        foreach($tokens as $i => $token) 
            if( $index = FunctionCall::isGlobalCall("dd", $tokens, $i)) break;

        $this->assertNotNull($index);
        $this->assertEquals(27, $index);

    }

}
