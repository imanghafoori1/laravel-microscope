<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class ClassMethods
{
    public static function read($tokens)
    {
        $i = 0;
        $class = [
            'name' => null,
            'methods' => [],
            'type' => '',
        ];
        $methods = [];
        while (isset($tokens[$i])) {
            $token = $tokens[$i];

            if ($token[0] == T_CLASS && $tokens[$i - 1][0] !== T_DOUBLE_COLON) {
                $class['name'] = $tokens[$i + 2];
                $class['type'] = T_CLASS;
            } elseif ($token[0] == T_INTERFACE) {
                $class['name'] = $tokens[$i + 2];
                $class['type'] = T_INTERFACE;
            } elseif ($token[0] == T_TRAIT) {
                $class['name'] = $tokens[$i + 2];
                $class['type'] = T_TRAIT;
            }

            if ($class['name'] === null || $tokens[$i][0] != T_FUNCTION) {
                $i++;
                continue;
            }

            $name = $tokens[$i + 2];
            if (in_array($tokens[$i - 2][0], [T_PUBLIC, T_PROTECTED, T_PRIVATE])) {
                $visibility = $tokens[$i - 2];
            } else {
                $visibility = T_PUBLIC;
            }

            [, $signature, $endSignature] = Ifs::readCondition($tokens, $i + 2);
            [$char, $charIndex] = FunctionCall::forwardTo($tokens, $endSignature, [':', ';', '{']);
            if ($char == ':') {
                [$returnType, $returnTypeIndex] = FunctionCall::getNextToken($tokens, $charIndex);
                [$char, $charIndex] = FunctionCall::getNextToken($tokens, $returnTypeIndex);
            } else {
                $returnType = null;
            }

            if ($char == '{') {
                [$body, $i] = FunctionCall::readBody($tokens, $charIndex);
            } elseif ($char == ';') {
                $body = [];
            }
            $i++;
            $methods[] = [
                'name' => $name,
                'visibility' => $visibility,
                'signature' => $signature,
                'body' => Refactor::toString($body),
                'startBodyIndex' => [$charIndex, $i],
                'returnType' => $returnType,

            ];
        }

        $class['methods'] = $methods;

        return $class;
    }
}
