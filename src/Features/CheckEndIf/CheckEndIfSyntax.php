<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEndIf;

use Exception;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\Console;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\SyntaxNormalizer;

class CheckEndIfSyntax implements Check
{
    use CachedCheck;

    public static $cacheKey = 'check_ruby_syntax';

    public static bool $ask = true;

    public static function performCheck(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();
        if (empty($tokens) || $tokens[0][0] !== T_OPEN_TAG) {
            return false;
        }

        try {
            $tokens = SyntaxNormalizer::normalizeSyntax($tokens, true);
            $hasChange = SyntaxNormalizer::$hasChange;
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            return false;
        }
        // @codeCoverageIgnoreEnd

        if ($hasChange) {
            ErrorPrinter::singleton()->count++;
        }

        if ($hasChange && (! self::$ask || self::getConfirm($file))) {
            $file->saveTokens($tokens);

            return false;
        }

        return (bool) $hasChange;
    }

    private static function getConfirm(PhpFileDescriptor $file)
    {
        return Console::confirm(self::confirm($file));
    }

    public static function confirm(PhpFileDescriptor $file)
    {
        return 'Replacing endif in: '.Color::blue($file->relativePath());
    }
}
