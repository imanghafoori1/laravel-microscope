<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEnvCalls;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\FunctionCall;
use Imanghafoori\TokenAnalyzer\TokenManager;

class EnvCallsCheck implements Check
{
    use CachedCheck;

    public static $onErrorCallback;

    /**
     * @var string
     */
    private static $cacheKey = 'env_calls_command';

    public static function performCheck(PhpFileDescriptor $file): bool
    {
        $onError = self::$onErrorCallback;
        $absPath = $file->getAbsolutePath();
        $tokens = $file->getTokens();

        $hasError = false;
        foreach ($tokens as $i => $token) {
            if ($index = FunctionCall::isGlobalCall('env', $tokens, $i)) {
                if (! self::isLikelyConfigFile($absPath, $tokens)) {
                    $onError($tokens[$index][1], $absPath, $tokens[$index][2]);
                    $hasError = true;
                }
            }
        }

        return $hasError;
    }

    private static function isLikelyConfigFile($absPath, $tokens)
    {
        [$token] = TokenManager::getNextToken($tokens, 0);

        if ($token[0] === T_NAMESPACE) {
            return false;
        }

        if ($token[0] === T_RETURN && strpos(strtolower($absPath), 'config')) {
            return true;
        }

        return basename($absPath) === 'config.php';
    }
}
