<?php

namespace Imanghafoori\LaravelMicroscope\Refactors;

class NestedIf
{
    public static function merge($tokens, $cond1EndIndex, $cond2StartIndex, $if2BodyEndIndex)
    {
        $newTokens = [];
        foreach ($tokens as $i => $oldToken) {
            if ($i == $cond1EndIndex) {
                $newTokens[] = [T_WHITESPACE, ' '];
                $newTokens[] = [T_BOOLEAN_AND, '&&'];
                $newTokens[] = [T_WHITESPACE, ' '];
                continue;
            }

            if ($i > $cond1EndIndex && $i <= $cond2StartIndex) {
                continue;
            }

            if ($i == $if2BodyEndIndex || ($i == $if2BodyEndIndex + 1 && $oldToken == ';')) {
                continue;
            }
            $newTokens[] = $oldToken;
        }

        return $newTokens;
    }
}
