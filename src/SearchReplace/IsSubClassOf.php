<?php

namespace Imanghafoori\LaravelMicroscope\SearchReplace;

use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class IsSubClassOf
{
    public static function check($placeholderVal, $parameter, $tokens)
    {
        $fullClassPath = ParseUseStatement::getExpandedRef($tokens, $placeholderVal[1]);

        return is_subclass_of($fullClassPath, $parameter);
    }
}
