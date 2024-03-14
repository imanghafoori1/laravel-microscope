<?php

namespace Imanghafoori\LaravelMicroscope\SearchReplace;

use Illuminate\Support\Str;
use Imanghafoori\TokenAnalyzer\GetClassProperties;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class FullNamespaceIs
{
    public static function check($placeholderVal, $parameter, $tokens)
    {
        if ($placeholderVal[1] === 'self') {
            [$namespace, $class] = GetClassProperties::readClassDefinition($tokens);
            $fullClassPath = $namespace.'\\'.$class;
        } else {
            $fullClassPath = ParseUseStatement::getExpandedRef($tokens, $placeholderVal[1]);
        }

        return Str::is($parameter, $fullClassPath);
    }
}
