<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Exception;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\TokenAnalyzer\Refactor;
use Imanghafoori\TokenAnalyzer\SyntaxNormalizer;

class CheckEarlyReturn implements Check
{
    public static function check($tokens, $absFilePath, $isTest)
    {
        if (empty($tokens) || $tokens[0][0] !== T_OPEN_TAG) {
            return false;
        }

        try {
            $tokens = SyntaxNormalizer::normalizeSyntax($tokens, true);
        } catch (Exception $e) {
            self::requestIssue($absFilePath);

            return false;
        }

        if (SyntaxNormalizer::$hasChange && self::getConfirm($absFilePath)) {
            Refactor::saveTokens($absFilePath, $tokens, $isTest);

            return true;
        }

        return false;
    }

    private static function getConfirm($filePath)
    {
        $filePath = FilePath::getRelativePath($filePath);

        return ErrorPrinter::singleton()->printer->confirm('Replacing endif in: '.$filePath);
    }

    private static function requestIssue(string $path)
    {
        dump('(O_o)   Well, It seems we had some problem parsing the contents of:   (o_O)');
        dump('Submit an issue on github: https://github.com/imanghafoori1/microscope');
        dump('Send us the contents of: '.$path);
    }
}
