<?php

namespace Imanghafoori\LaravelMicroscope\Refactor;

class Stringify
{
    public static function fromTokens($tokens)
    {
        $string = '';

        foreach ($tokens as $token) {
            $string .= $token[1] ?? $token[0];
        }

        return $string;
    }
}
