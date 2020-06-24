<?php

namespace Imanghafoori\LaravelMicroscope\Checks\PSR12;

use Imanghafoori\LaravelMicroscope\Analyzers\Refactor;

class CurlyBraces
{
    public static $command;

    public static function check($tokens, $absFilePath, $classFilePath, $psr4Path, $psr4Namespace)
    {
        self::removeGenericDocBlocks($tokens, $classFilePath);
    }

    private static function removeGenericDocBlocks($tokens, $classFilePath)
    {
        $level = 0;
        $isInSideClass = false;
        $spliced = 0;
        $ct = count($tokens);
        $i = 0;
        while ($i < $ct - 1) {
            $i++;
            $token = $tokens[$i];
            $token[0] == '{' && $level++;
            $token[0] == '}' && $level--;
            if ($level == 0) {
                if (in_array($token[0], [T_CLASS, T_TRAIT, T_INTERFACE])) {
                    if ($tokens[$i - 1] != T_DOUBLE_COLON) {
                        $isInSideClass = true;
                    }
                }
            }
            self::openCurly($token, $level, $tokens, $i, $classFilePath);

            [$tokens, $i] = self::writePublic($level, $token, $isInSideClass, $i, $tokens, $classFilePath);
        }
    }

    private static function openCurly($token, int $level, $tokens, $i, $classFilePath): void
    {
        if ($token == '{') {
            $sp = str_repeat('    ', $level);
            if ($tokens[$i + 1][0] == T_WHITESPACE) {
                if ($tokens[$i + 1][1] != PHP_EOL.$sp && $tokens[$i + 1][1] != "\n".$sp) {
                    $tokens[$i + 1][1] = PHP_EOL.$sp;
                    Refactor::saveTokens($classFilePath->getRealpath(), $tokens);
                } else {
                }
            } else {
                array_splice($tokens, $i + 1, 0, [[T_WHITESPACE, PHP_EOL.$sp]]);
                Refactor::saveTokens($classFilePath->getRealpath(), $tokens);
            }
        }
    }

    private static function writePublic(int $level, $token, bool $isInSideClass, int $i, $tokens, $classFilePath): array
    {
        if ($level == 1 && $token == T_FUNCTION && $isInSideClass) {
            $t = $i;
            if (in_array($tokens[$t - 2][0], [T_STATIC])) {
                $t = $t - 2;
            }

            if (! in_array($tokens[$t - 2][0], [T_PUBLIC, T_PROTECTED, T_PRIVATE])) {
                array_splice($tokens, $t, 0, [[T_WHITESPACE, ' ']]);
                array_splice($tokens, $t, 0, [[T_PUBLIC, 'public']]);
                $i++;
                $i++;
                Refactor::saveTokens($classFilePath->getRealpath(), $tokens);
            }
        }

        return [$tokens, $i];
    }
}
