<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEndIf;

use Exception;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\Refactor;
use Imanghafoori\TokenAnalyzer\SyntaxNormalizer;

class CheckRubySyntax implements Check
{
    use CachedCheck;

    public static $cacheKey = 'check_ruby_syntax';

    public static function performCheck(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();
        if (empty($tokens) || $tokens[0][0] !== T_OPEN_TAG) {
            return false;
        }

        $absFilePath = $file->getAbsolutePath();

        try {
            $tokens = SyntaxNormalizer::normalizeSyntax($tokens, true);
        // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            self::requestIssue($absFilePath);

            return false;
        }
        // @codeCoverageIgnoreEnd

        if (SyntaxNormalizer::$hasChange && self::getConfirm($absFilePath)) {
            Refactor::saveTokens($absFilePath, $tokens);

            return true;
        }

        return false;
    }

    private static function getConfirm($filePath)
    {
        $filePath = FilePath::getRelativePath($filePath);

        return ErrorPrinter::singleton()->printer->confirm('Replacing endif in: '.Color::blue($filePath));
    }

    /**
     * @codeCoverageIgnore
     */
    private static function requestIssue($path)
    {
        dump('(O_o)   Well, It seems we had some problem parsing the contents of:   (o_O)');
        dump('Submit an issue on github: https://github.com/imanghafoori1/microscope');
        dump('Send us the contents of: '.$path);
    }
}
