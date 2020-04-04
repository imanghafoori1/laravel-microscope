<?php

namespace Imanghafoori\LaravelSelfTest;

class GetClassProperties
{
    public static function fromFilePath($filePath)
    {
        $fp = fopen($filePath, 'r');
        $type = $class = $namespace = $buffer = '';
        $i = 0;
        while (! $class) {
            if (feof($fp)) {
                break;
            }

            $buffer .= fread($fp, 1200);
            $tokens = token_get_all($buffer.'/**/');

            if (strpos($buffer, '{') === false) {
                continue;
            }

            [
                $namespace,
                $type,
                $class,
            ] = self::getImports($i, $tokens, $namespace);
        }

        return [
            ltrim($namespace, '\\'),
            $class,
            $type,
        ];
    }

    protected static function getImports($i, array $tokens, $namespace)
    {
        $type = $class = null;
        for (; $i < count($tokens); $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                $tCount = count($tokens);
                for ($j = $i + 1; $j < $tCount; $j++) {
                    if ($tokens[$j][0] === T_STRING) {
                        $namespace .= '\\'.$tokens[$j][1];
                    } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                        // go ahead until you reach:
                        // 1. an opening curly brace {
                        // 2. or a semi-colon ;
                        break;
                    }
                }
            }

            if (! $class && $tokens[$i][0] == T_DOUBLE_COLON) {
                return [$namespace, null, null,];
            }

            if (! $class && in_array($tokens[$i][0], [
                    T_CLASS,
                    T_INTERFACE,
                    T_TRAIT,
                ])) {
                $type = $tokens[$i][0];
                $tCount = count($tokens);

                if ($tokens[$i-1][0] == T_DOUBLE_COLON) {
                    return [$namespace, null, null,];
                }

                for ($j = $i + 1; $j < $tCount; $j++) {
                    if (! $class && $tokens[$j] === '{') {
                        $class = $tokens[$i + 2][1];
                        break;
                    }
                }
            }
        }

        return [
            $namespace,
            $type,
            $class,
        ];
    }
}
