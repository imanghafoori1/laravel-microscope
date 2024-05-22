<?php

namespace Imanghafoori\LaravelMicroscope\Checks\PSR12;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\Refactor;

class CurlyBraces implements Check
{
    public static $command;

    public static function check(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();
        $absFilePath = $file->getAbsolutePath();

        self::addPublicKeyword($tokens, $absFilePath);
    }

    private static function addPublicKeyword($tokens, $absolutePath)
    {
        $level = 0;
        $isInSideClass = false;
        $ct = \count($tokens);
        $i = 0;
        while ($i < $ct - 1) {
            $i++;
            $token = $tokens[$i];
            in_array($token[0], [T_CURLY_OPEN, '{'], true) && $level++;
            if ($token[0] === '}') {
                $level--;
                ($isInSideClass === true && $level === 0) && ($isInSideClass = false);
            }
            if (self::isGoingInsideClass($level, $token[0], $tokens[$i - 1][0])) {
                $isInSideClass = true;
            }
            self::openCurly($token, $level, $tokens, $i, $absolutePath);

            [$tokens, $i] = self::writePublic($level, $token, $isInSideClass, $i, $tokens, $absolutePath);
        }
    }

    private static function openCurly($token, $level, $tokens, $i, $absFilePath)
    {
        if ($token == '{' && ! in_array($tokens[$i - 1][0], [T_DOUBLE_COLON, T_OBJECT_OPERATOR])) {
            $sp = str_repeat('    ', $level);
            if ($tokens[$i + 1][0] === T_WHITESPACE) {
                if ($tokens[$i + 1][1] !== PHP_EOL.$sp && $tokens[$i + 1][1] !== "\n".$sp) {
                    $tokens[$i + 1][1] = PHP_EOL.$sp;
                    Refactor::saveTokens($absFilePath, $tokens);
                } else {
                    //
                }
            } else {
                array_splice($tokens, $i + 1, 0, [[T_WHITESPACE, PHP_EOL.$sp]]);
                Refactor::saveTokens($absFilePath, $tokens);
            }
        }
    }

    private static function writePublic($level, $token, $isInClass, $i, $tokens, $absolutePath)
    {
        if (($level !== 1) || ($token[0] !== T_FUNCTION) || ! $isInClass) {
            return [$tokens, $i];
        }

        $t = $i;
        if (in_array($tokens[$t - 2][0], [T_STATIC])) {
            $t = $t - 2;
        }

        if (! in_array($tokens[$t - 2][0], [T_PUBLIC, T_PROTECTED, T_PRIVATE])) {
            array_splice($tokens, $t, 0, [[T_WHITESPACE, ' ']]);
            array_splice($tokens, $t, 0, [[T_PUBLIC, 'public']]);
            $i++;
            $i++;
            Refactor::saveTokens($absolutePath, $tokens);
        }

        return [$tokens, $i];
    }

    private static function isGoingInsideClass($level, $token, $previousToken): bool
    {
        return $level === 0 && in_array($token, [T_CLASS, T_TRAIT, T_INTERFACE]) && $previousToken !== T_DOUBLE_COLON;
    }
}
