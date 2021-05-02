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

            if (! \is_array($name = $tokens[$i + 2])) {
                $i++;
                continue;
            }

            [$visibility, $isStatic] = self::findVisibility($tokens, $i - 2);
            [, $signature, $endSignature] = Ifs::readCondition($tokens, $i + 2);
            [$char, $charIndex] = FunctionCall::forwardTo($tokens, $endSignature, [':', ';', '{']);

            [$returnType, $hasNullableReturnType, $char, $charIndex] = self::processReturnType($char, $tokens, $charIndex);

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
                'nullable_return_type' => $hasNullableReturnType,
                'is_static' => $isStatic,
            ];
        }

        $class['methods'] = $methods;

        return $class;
    }

    private static function findVisibility($tokens, $i)
    {
        $isStatic = false;
        if ($tokens[$i][0] == T_STATIC) {
            $i = $i - 2;
            $isStatic = true;
        }

        if (\in_array($tokens[$i][0], [T_PUBLIC, T_PROTECTED, T_PRIVATE])) {
            return [$tokens[$i], $isStatic];
        } else {
            return [[T_PUBLIC, 'public'], $isStatic];
        }
    }

    private static function processReturnType($char, $tokens, $charIndex)
    {
        if ($char != ':') {
            return [null, null, $char, $charIndex];
        }

        [$returnType, $returnTypeIndex] = FunctionCall::getNextToken($tokens, $charIndex);

        // In case the return type is like this: function c() : ?string {...
        $hasNullableReturnType = ($returnType == '?');

        if ($hasNullableReturnType) {
            [$returnType, $returnTypeIndex] = FunctionCall::getNextToken($tokens, $returnTypeIndex);
        }


        [$char, $charIndex] = FunctionCall::getNextToken($tokens, $returnTypeIndex);

        return [$returnType, $hasNullableReturnType, $char, $charIndex];
    }
}
