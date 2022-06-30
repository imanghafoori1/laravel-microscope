<?php

namespace Imanghafoori\LaravelMicroscope\SearchReplace;

use Imanghafoori\TokenAnalyzer\GetClassProperties;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class IsSubClassOf
{
    public static function check($placeholderVal, $parameter, $tokens)
    {
        if ($placeholderVal[1] === 'self') {
            [$namespace, $class] = GetClassProperties::readClassDefinition($tokens);
            $fullClassPath = $namespace.'\\'.$class;
        } else {
            $fullClassPath = ParseUseStatement::getExpandedRef($tokens, $placeholderVal[1]);
        }

        return is_subclass_of($fullClassPath, $parameter);
    }
}
