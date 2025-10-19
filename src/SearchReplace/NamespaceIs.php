<?php

namespace Imanghafoori\LaravelMicroscope\SearchReplace;

use Illuminate\Support\Str;
use Imanghafoori\TokenAnalyzer\GetClassProperties;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class NamespaceIs
{
    /**
     * @return bool
     */
    public static function check($placeholderVal, $parameter, $tokens)
    {
        if ($placeholderVal[1] === 'self') {
            [$namespace] = GetClassProperties::readClassDefinition($tokens);
        } else {
            if ($placeholderVal[1][0] !== '\\') {
                $namespaceClass = ParseUseStatement::getExpandedRef($tokens, $placeholderVal[1]);
                $segments = explode('\\', $namespaceClass);
            } else {
                $segments = explode('\\', $placeholderVal[1]);
            }
            array_pop($segments);
            $namespace = implode('\\', $segments);
        }

        return Str::is($parameter, $namespace);
    }
}
