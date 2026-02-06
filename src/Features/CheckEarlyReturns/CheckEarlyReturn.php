<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEarlyReturns;

use Exception;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\Refactor;

class CheckEarlyReturn implements Check
{
    public static $params = [];

    public static function check(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();
        $absFilePath = $file->getAbsolutePath();

        $nofix = self::$params['nofix'];
        $nofixCallback = self::$params['nofixCallback'];

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

        if ($nofix) {
            $nofixCallback($file);
        } elseif (self::getConfirm($absFilePath)) {
            self::fix($file, $tokens, $fixes);
        }
    }

    private static function getConfirm($absFilePath)
    {
        $relFilePath = FilePath::getRelativePath($absFilePath);
        $question = ' Do you want to flatten: '.Color::yellow($relFilePath);

        return ErrorPrinter::singleton()->printer->confirm($question);
    }

    private static function refactor($tokens)
    {
        $fixes = 0;
        do {
            [$tokens, $refactored] = Refactor::flatten($tokens);
        } while ($refactored > 0 && $fixes++);

        return [$fixes, $tokens];
    }

    private static function fix(PhpFileDescriptor $file, $tokens, $fixes)
    {
        $file->saveTokens($tokens);
        $fixCallback = self::$params['fixCallback'];
        $fixCallback($file, $fixes);
    }
}
