<?php

namespace Imanghafoori\LaravelMicroscope\SearchReplace;

use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class IsSubClassOf
{
    public static function check($placeholderVal, $parameter, $tokens): bool
    {
        $className = $placeholderVal[1];
        $refs = ParseUseStatement::parseUseStatements($tokens, $className);

        $fullPath = $refs[1][$className][0] ?? $className;

        return is_subclass_of($fullPath, $parameter);
    }
}
