<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\TokenAnalyzer\FunctionCall;

class FunctionCallTest extends BaseTestClass
{
    /** @test */
    public function has_dd_test()
    {
        $tokens = token_get_all(file_get_contents(__DIR__.'/stubs/function_test/some_function.sub'));

        $index = null;
        foreach ($tokens as $i => $token) {
            if ($index = FunctionCall::isGlobalCall('dd', $tokens, $i)) {
                break;
            }
        }

        $this->assertNotNull($index);
        $this->assertEquals($index, 27);
    }

    /** @test */
    public function count_arraysum_test()
    {
        $tokens = token_get_all(file_get_contents(__DIR__.'/stubs/function_test/some_function.sub'));

        $countArraySum = 0;
        foreach ($tokens as $i => $token) {
            if (FunctionCall::isGlobalCall('array_sum', $tokens, $i)) {
                $countArraySum++;
            }
        }

        $this->assertEquals($countArraySum, 4);
    }

    /** @test */
    public function is_static_call_without_send_classname_test()
    {
        $tokens = token_get_all(file_get_contents(__DIR__.'/stubs/function_test/some_function.sub'));

        $countArraySum = 0;
        foreach ($tokens as $i => $token) {
            if (FunctionCall::isStaticCall('_3', $tokens, $i)) {
                $countArraySum++;
            }
        }

        $this->assertEquals($countArraySum, 5);
    }
}
