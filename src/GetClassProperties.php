<?php

namespace Imanghafoori\LaravelMicroscope;

class GetClassProperties
{
    public static function fromFilePath($filePath)
    {
        $fp = fopen($filePath, 'r');
        $buffer = fread($fp, 2000);
        $tokens = token_get_all($buffer.'/**/');

        if (strpos($buffer, '{') === false) {
            return [
                null,
                null,
                null,
                null,
            ];
        }

        [
            $namespace,
            $type,
            $class,
            $parent
        ] = self::readClassDefinition($tokens);

        return [
            ltrim($namespace, '\\'),
            $class,
            $type,
            $parent,
        ];
    }

    protected static function readClassDefinition($tokens)
    {
        $type = $class = null;
        $allTokensCount = count($tokens);
        $parent = null;
        $namespace = null;
        for ($i = 0; $i < $allTokensCount; $i++) {
            if (! $namespace) {
                [$i, $namespace] = self::collectForKeyWord($tokens, $i, T_NAMESPACE);
            }

            // if we reach a double colon before a class keyword
            // it means that, it is not a psr-4 class.
            if (! $class && $tokens[$i][0] == T_DOUBLE_COLON) {
                return [$namespace, null, null, null];
            }

            // when we reach the first "class", or "interface" or "trait" keyword
            if (! $class && in_array($tokens[$i][0], [
                T_CLASS,
                T_INTERFACE,
                T_TRAIT,
            ])) {
                $class = $tokens[$i + 2][1];
                $type = $tokens[$i + 2][0];
                $i = $i + 2;
                continue;
            }

            if (! $parent) {
                [$i, $parent] = self::collectForKeyWord($tokens, $i, T_EXTENDS);
            }
        }

        return [
            $namespace,
            $type,
            $class,
            $parent,
        ];
    }

    /**
     * @param $tokens
     * @param  int  $i
     * @param  int  $target
     *
     * @return array
     */
    protected static function collectForKeyWord($tokens, int $i, $target)
    {
        $namespace = '';
        if ($tokens[$i][0] === $target) {
            while (true) {
                $i++;
                // ignore white spaces
                if ($tokens[$i][0] === T_WHITESPACE) {
                    continue;
                }

                if ($tokens[$i] === '{' || $tokens[$i] === ';' || ! isset($tokens[$i])) {
                    // we go ahead and collect until we reach:
                    // 1. an opening curly brace {
                    // 2. or a semi-colon ;
                    // 3. end of tokens.
                    break;
                }

                $namespace .= $tokens[$i][1];
            }
        }

        return [
            $i,
            $namespace,
        ];
    }
}
