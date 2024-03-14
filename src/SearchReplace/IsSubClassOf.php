<?php

namespace Imanghafoori\LaravelMicroscope\SearchReplace;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\TokenAnalyzer\GetClassProperties;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class IsSubClassOf implements Check
{
    public static function check($placeholderVal, $parameter, $tokens)
    {
        $fullClassPath = self::getFullClassPath($placeholderVal[1], $tokens);

        return is_subclass_of($fullClassPath, $parameter);
    }

    private static function getFullClassPath($classRef, $tokens): string
    {
        if ($classRef === 'self') {
            [$namespace, $class] = GetClassProperties::readClassDefinition($tokens);

            return $namespace.'\\'.$class;
        }

        if ($classRef === 'parent') {
            return self::getParent($tokens);
        }

        if ($classRef[0] === '\\') {
            return $classRef;
        }

        return ParseUseStatement::getExpandedRef($tokens, $classRef);
    }

    private static function getParent($tokens)
    {
        [$namespace, $class, $type] = GetClassProperties::readClassDefinition($tokens);

        return $type === T_CLASS ? get_parent_class($namespace.'\\'.$class) : '';
    }
}
