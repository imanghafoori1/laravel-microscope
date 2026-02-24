<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDD;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\FunctionCall;

class CheckDD implements Check
{
    use CachedCheck;

    public static $errorHandler = CheckDDHandler::class;

    private static $cacheKey = 'check_dd_command';

    /**
     * @param \Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor $file
     * @return bool
     */
    public static function performCheck(PhpFileDescriptor $file)
    {
        $errors = self::getErrors($file);

        return self::$errorHandler::handle($file, $errors);
    }

    /**
     * @param \Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor $file
     * @return \Generator<int, int>
     */
    private static function getErrors(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();
        foreach ($tokens as $i => $token) {
            // make the function check case-insensitive:
            $name = strtolower($token[1] ?? '');
            if ($name === 'dump' || $name === 'dd') {
                $tokens[$i][1] = $name;

                continue;
            }

            if (($index = FunctionCall::isGlobalCall('dd', $tokens, $i)) || ($index = FunctionCall::isGlobalCall('dump', $tokens, $i)) || ($index = FunctionCall::isGlobalCall('ddd', $tokens, $i))) {
                yield $index;
            }
        }
    }
}
