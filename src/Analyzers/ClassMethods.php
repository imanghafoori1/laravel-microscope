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
                $class['is_abstract'] = ($tokens[$i - 2][0] === T_ABSTRACT);
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

            [$visibility, $isStatic, $isAbstract] = self::findVisibility($tokens, $i - 2);
            [, $signature, $endSignature] = Ifs::readCondition($tokens, $i + 2);
            [$char, $charIndex] = FunctionCall::forwardTo($tokens, $endSignature, [':', ';', '{']);

            [$returnType, $hasNullableReturnType, $char, $charIndex] = self::processReturnType($char, $tokens, $charIndex);

            if ($char == '{') {
                [$body, $i] = FunctionCall::readBody($tokens, $charIndex);
            } elseif ($char == ';') {
                $body = [];
            } else {
                $code = Refactor::toString($tokens);
                self::requestIssue($code);
                break;
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
                'is_abstract' => $isAbstract,
            ];
        }

        $class['methods'] = $methods;

        return $class;
    }

    private static function requestIssue($content)
    {
        dump('(O_o)   Well, It seems we had some problem parsing the contents of:   (o_O)');
        dump('Submit an issue on github: https://github.com/imanghafoori1/microscope');
        dump('Send us the content and mention your php version ('.phpversion().')');
        dump($content);
    }

    private static function findVisibility($tokens, $i)
    {
        $isStatic = $tokens[$i][0] === T_STATIC && $i -= 2;
        $isAbstract = $tokens[$i][0] === T_ABSTRACT && $i -= 2;

        $hasModifier = \in_array($tokens[$i][0], [T_PUBLIC, T_PROTECTED, T_PRIVATE]);
        $visibility = $hasModifier ? $tokens[$i] : [T_PUBLIC, 'public'];

        // We have to cover both syntax:
        //     public abstract function x() {
        //     abstract public function x() {
        ! $isAbstract && $isAbstract = $tokens[$i - 2][0] === T_ABSTRACT;

        return [$visibility, $isStatic, $isAbstract];
    }

    private static function processReturnType($endingChar, $tokens, $charIndex)
    {
        // No return type is defined.
        if ($endingChar != ':') {
            return [null, null, $endingChar, $charIndex];
        }

        [$returnType, $returnTypeIndex] = FunctionCall::getNextToken($tokens, $charIndex);

        // In case the return type is like this: function c() : ?string {...
        $hasNullableReturnType = ($returnType == '?');

        if ($hasNullableReturnType) {
            [$returnType, $returnTypeIndex] = FunctionCall::getNextToken($tokens, $returnTypeIndex);
        }

        [$endingChar, $charIndex] = FunctionCall::getNextToken($tokens, $returnTypeIndex);

        $returnType = [$returnType];

        while ($endingChar == '|') {
            [$returnType2, $charIndex] = FunctionCall::getNextToken($tokens, $charIndex);
            $returnType[] = $returnType2;
            [$endingChar, $charIndex] = FunctionCall::getNextToken($tokens, $charIndex);
        }

        return [$returnType, $hasNullableReturnType, $endingChar, $charIndex];
    }
}
