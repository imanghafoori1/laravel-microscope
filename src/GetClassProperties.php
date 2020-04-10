<?php

namespace Imanghafoori\LaravelMicroscope;

class GetClassProperties
{
    public static function fromFilePath($filePath)
    {
        $fp = fopen($filePath, 'r');
        $type = $class = $namespace = $buffer = '';
        $i = 0;
        while (! $class) {

            // finish when we reached end of file
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
                $parent
            ] = self::readClassDefinition($tokens);
        }

        return [
            ltrim($namespace, '\\'),
            $class,
            $type,
        ];
    }

    protected static function readClassDefinition($tokens)
    {
        $namespace = '';
        $type = $class = null;
        $allTokensCount = count($tokens);
        $parent = null;
        for ($i = 0; $i < $allTokensCount; $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
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

                    $namespace .= '\\'.$tokens[$i][1];
                }
            }

            // if we reach a double colon before a class keyword
            // it means that, it is not a psr-4 class.
            if (! $class && $tokens[$i][0] == T_DOUBLE_COLON) {
                return [$namespace, null, null];
            }

            // when we reach the first "class", or "interface" or "trait" keyword
            if (! $class && in_array($type, [
                    T_CLASS,
                    T_INTERFACE,
                    T_TRAIT,
                ])) {
                $class = $tokens[$i + 2][1];
                $type = $tokens[$i + 2][0];
                $i = $i + 3;
                continue;
            }

            if ($type == T_EXTENDS) {
                $parent = $tokens[$i + 2];
            }
        }

        return [
            $namespace,
            $type,
            $class,
            $parent,
        ];
    }
}
