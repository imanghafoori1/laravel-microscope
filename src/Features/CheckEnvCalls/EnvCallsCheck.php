<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEnvCalls;

use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\FunctionCall;
use Imanghafoori\TokenAnalyzer\TokenManager;

class EnvCallsCheck
{
    public static function check(PhpFileDescriptor $file): array
    {
        $tokens = $file->getTokens();
        $absPath = $file->getAbsolutePath();

        foreach ($tokens as $i => $token) {
            if ($index = FunctionCall::isGlobalCall('env', $tokens, $i)) {
                if (! self::isLikelyConfigFile($absPath, $tokens)) {
                    EnvFound::warn($absPath, $tokens[$index][2], $tokens[$index][1]);
                }
            }
        }

        return $tokens;
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