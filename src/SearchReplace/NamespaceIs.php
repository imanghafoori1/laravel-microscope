<?php

namespace Imanghafoori\LaravelMicroscope\SearchReplace;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\TokenAnalyzer\GetClassProperties;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class NamespaceIs implements Check
{
    public static function check($placeholderVal, $parameter, $tokens)
    {
        if ($placeholderVal[1] === 'self') {
            [$namespace] = GetClassProperties::readClassDefinition($tokens);
        } else {
            $namespaceClass = ParseUseStatement::getExpandedRef($tokens, $placeholderVal[1]);
            $segments = explode('\\', $namespaceClass);
            array_pop($segments);
            $namespace = implode('\\', $segments);
        }

        return Str::is($parameter, $namespace);
    }
}
