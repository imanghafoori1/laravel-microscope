<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class GetClassProperties
{
    public static function fromFilePath($filePath)
    {
        $fp = fopen($filePath, 'r');
        $buffer = fread($fp, 2000);
        $tokens = token_get_all($buffer.'/**/');

        if (strpos($buffer, '{') === false) {
            return [null, null, null, null];
        }

        return self::readClassDefinition($tokens);
    }

    public static function readClassDefinition($tokens)
    {
        $type = $class = null;
        $allTokensCount = count($tokens);
        $parent = null;
        $interfaces = $namespace = '';

        for ($i = 1; $i < $allTokensCount; $i++) {
            if (! $namespace) {
                [$i, $namespace,] = self::collectAfterKeyword($tokens, $i, T_NAMESPACE);
            }

            // if we reach a double colon before a class keyword
            // it means that, it is not a psr-4 class.
            if (! $class && $tokens[$i][0] == T_DOUBLE_COLON) {
                return [$namespace, null, null, null, null];
            }

            // when we reach the first "class", or "interface" or "trait" keyword
            if (! $class && in_array($tokens[$i][0], [T_CLASS, T_INTERFACE, T_TRAIT])) {
                $class = $tokens[$i + 2][1];
                $type = $tokens[$i + 2][0];
                $i = $i + 2;
                continue;
            }

            if (! $parent) {
                [$i, $parent] = self::collectAfterKeyword($tokens, $i, T_EXTENDS, [T_IMPLEMENTS]);
            }

            if (! $interfaces) {
                [$i, $interfaces] = self::collectAfterKeyword($tokens, $i, T_IMPLEMENTS, [], ',');
            }
        }

        return [
            ltrim($namespace, '\\'),
            $class,
            $type,
            $parent,
            $interfaces,
        ];
    }

    /**
     * @param $tokens
     * @param  int  $i
     * @param  int  $target
     * @param  array  $terminators
     * @param  string|null  $separator
     *
     * @return array
     */
    protected static function collectAfterKeyword($tokens, $i, $target, $terminators = [], $separator = null)
    {
        $terminators[] = ';';
        $terminators[] = '{';

        $results = '';
        if ($tokens[$i][0] !== $target) {
            return [$i, $results];
        }

        while (true) {
            $i++;
            // ignore white spaces
            if ($tokens[$i][0] === T_WHITESPACE) {
                continue;
            }

            if (($tokens[$i][0] == $separator)) {
                $results .= '|';
                $i++;
                continue;
            }

            if (in_array($tokens[$i][0], $terminators) || ! isset($tokens[$i])) {
                // we go ahead and collect until we reach:
                // 1. an opening curly brace {
                // 2. or a semi-colon ;
                // 3. end of tokens.

                return [$i, $results];
            }

            $results .= $tokens[$i][1];
        }

        return [$i, $results];
    }
}
