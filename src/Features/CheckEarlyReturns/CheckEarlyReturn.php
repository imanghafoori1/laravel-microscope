<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEarlyReturns;

use Exception;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\Console;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\Refactor;

class CheckEarlyReturn implements Check
{
    public static $noFix = false;

    public static function check(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();

        if (empty($tokens) || $tokens[0][0] !== T_OPEN_TAG) {
            return;
        }

        // @codeCoverageIgnoreStart
        try {
            [$fixes, $tokens] = self::refactor($tokens);
        } catch (Exception $e) {
            return;
        }
        // @codeCoverageIgnoreEnd

        if ($fixes === 0) {
            return;
        }

        if (self::$noFix) {
            Console::getInstance()->writeln(PHP_EOL.'    - '.Color::red($file->relativePath()));
        } elseif (self::getConfirm($file)) {
            $file->saveTokens($tokens);

            self::printFixMsg($file, $fixes);
        }
    }

    private static function getConfirm(PhpFileDescriptor $file)
    {
        $question = ' Do you want to flatten: '.Color::yellow($file->relativePath());

        return Console::confirm($question);
    }

    private static function refactor($tokens)
    {
        $fixes = 0;
        do {
            [$tokens, $refactored] = Refactor::flatten($tokens);
        } while ($refactored > 0 && $fixes++);

        return [$fixes, $tokens];
    }

    private static function printFixMsg(PhpFileDescriptor $file, $fixes)
    {
        $s = $fixes > 1 ? 'es' : '';
        $file = Color::blue($file->getFileName());
        $msg = PHP_EOL.Color::red($fixes)." fix$s applied to: $file";

        Console::getInstance()->writeln($msg);
    }
}
