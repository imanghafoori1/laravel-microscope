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

    /**
     * @var \Closure
     */
    public static $onErrorCallback = EnvCallHandler::class;

    /**
     * @var string
     */
    private static $cacheKey = 'env_calls_command';

    public static function performCheck(PhpFileDescriptor $file): bool
    {
        $tokens = $file->getTokens();

        $hasError = false;
        foreach ($tokens as $i => $token) {
            if (strtolower($token[1] ?? '') === 'env') {
                $tokens[$i][1] = 'env';
                continue;
            }

            $index = FunctionCall::isGlobalCall('env', $tokens, $i);
            if (! $index) {
                continue;
            }
            if (self::isLikelyConfigFile($file, $tokens)) {
                continue;
            }
            self::$onErrorCallback::handle($file, $tokens[$index][1], $tokens[$index][2]);
            $hasError = true;
        }

        return $hasError;
    }

    private static function isLikelyConfigFile(PhpFileDescriptor $file, $tokens)
    {
        [$token] = TokenManager::getNextToken($tokens, 0);

        if ($token[0] === T_NAMESPACE) {
            return false;
        }

        if ($token[0] === T_RETURN && stripos($file->getAbsolutePath(), 'config')) {
            return true;
        }

        return $file->getFileName() === 'config.php';
    }
}
