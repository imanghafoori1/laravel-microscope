<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Exception;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\Refactor;

class CheckEarlyReturn implements Check
{
    public static function check(PhpFileDescriptor $file, $params)
    {
        $tokens = $file->getTokens();
        $absFilePath = $file->getAbsolutePath();

        $nofix = $params['nofix'];
        $nofixCallback = $params['nofixCallback'];
        $fixCallback = $params['fixCallback'];

        if (empty($tokens) || $tokens[0][0] !== T_OPEN_TAG) {
            return;
        }

        try {
            [$fixes, $tokens] = self::refactor($tokens);
        } catch (Exception $e) {
            dump('(O_o)   Well, It seems we had some problem parsing the contents of:  (O_o)');
            dump('Skipping : '.$absFilePath);

            return;
        }

        if ($fixes === 0) {
            return;
        }

        if ($nofix) {
            $nofixCallback($absFilePath);
        } elseif (self::getConfirm($absFilePath)) {
            self::fix($absFilePath, $tokens, $fixes, $fixCallback);
        }
    }

    private static function getConfirm($absFilePath)
    {
        $relFilePath = FilePath::getRelativePath($absFilePath);

        return ErrorPrinter::singleton()->printer->confirm(' Do you want to flatten: <fg=yellow>'.$relFilePath.'</>');
    }

    private static function refactor($tokens)
    {
        $fixes = 0;
        do {
            [$tokens, $refactored] = Refactor::flatten($tokens);
        } while ($refactored > 0 && $fixes++);

        return [$fixes, $tokens];
    }

    private static function fix($absFilePath, $tokens, $fixes, $fixCallback)
    {
        Refactor::saveTokens($absFilePath, $tokens);
        $fixCallback($absFilePath, $fixes);
    }
}
