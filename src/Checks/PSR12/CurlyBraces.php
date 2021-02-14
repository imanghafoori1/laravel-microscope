<?php

namespace Imanghafoori\LaravelMicroscope\Checks\PSR12;

use Imanghafoori\LaravelMicroscope\Analyzers\Refactor;

class CurlyBraces
{
    public static $command;

    public static function check($tokens, $absFilePath, $classFilePath, $psr4Path, $psr4Namespace)
    {
        self::removeGenericDocBlocks($tokens, $classFilePath->getRealpath());
    }

    private static function removeGenericDocBlocks($tokens, $classFilePath)
    {
        $level = 0;
        $isInSideClass = false;
        $ct = \count($tokens);
        $i = 0;
        while ($i < $ct - 1) {
            $i++;
            $token = $tokens[$i];
            \in_array($token[0], [T_CURLY_OPEN, '{']) && $level++;
            ($token[0] == '}') && $level--;
            if ($level == 0) {
                if (\in_array($token[0], [T_CLASS, T_TRAIT, T_INTERFACE])) {
                    if ($tokens[$i - 1] != T_DOUBLE_COLON) {
                        $isInSideClass = true;
                    }
                }
            }
            self::openCurly($token, $level, $tokens, $i, $classFilePath);

            [$tokens, $i] = self::writePublic($level, $token, $isInSideClass, $i, $tokens, $classFilePath);
        }
    }

    private static function openCurly($token, $level, $tokens, $i, $classFilePath)
    {
        if ($token == '{' && ! \in_array($tokens[$i - 1][0], [T_DOUBLE_COLON, T_OBJECT_OPERATOR])) {
            $sp = str_repeat('    ', $level);
            if ($tokens[$i + 1][0] == T_WHITESPACE) {
                if ($tokens[$i + 1][1] != PHP_EOL.$sp && $tokens[$i + 1][1] != "\n".$sp) {
                    $tokens[$i + 1][1] = PHP_EOL.$sp;
                    Refactor::saveTokens($classFilePath, $tokens);
                } else {
                    ///
                }
            } else {
                array_splice($tokens, $i + 1, 0, [[T_WHITESPACE, PHP_EOL.$sp]]);
                Refactor::saveTokens($classFilePath, $tokens);
            }
        }
    }

    private static function writePublic($level, $token, $isInClass, $i, $tokens, $absolutePath)
    {
        if (($level != 1) || ($token[0] != T_FUNCTION) || ! $isInClass) {
            return [$tokens, $i];
        }

        $t = $i;
        if (\in_array($tokens[$t - 2][0], [T_STATIC])) {
            $t = $t - 2;
        }

        if (! \in_array($tokens[$t - 2][0], [T_PUBLIC, T_PROTECTED, T_PRIVATE])) {
            array_splice($tokens, $t, 0, [[T_WHITESPACE, ' ']]);
            array_splice($tokens, $t, 0, [[T_PUBLIC, 'public']]);
            $i++;
            $i++;
            Refactor::saveTokens($absolutePath, $tokens);
        }

        return [$tokens, $i];
    }
}
