<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEnvCalls;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\TokenAnalyzer\FunctionCall;
use Imanghafoori\TokenAnalyzer\TokenManager;

class EnvCallsCheck implements Check
{
    public static function check(PhpFileDescriptor $file, $params)
    {
        if (CachedFiles::isCheckedBefore('env_calls_command', $file)) {
            return;
        }
        $onErrorCallback = $params[0];
        $absPath = $file->getAbsolutePath();
        $tokens = $file->getTokens();

        $hasError = false;
        foreach ($tokens as $i => $token) {
            if ($index = FunctionCall::isGlobalCall('env', $tokens, $i)) {
                if (! self::isLikelyConfigFile($absPath, $tokens)) {
                    $onErrorCallback($tokens[$index][1], $absPath, $tokens[$index][2]);
                    $hasError = true;
                }
            }
        }

        if ($hasError === false) {
            CachedFiles::put('env_calls_command', $file);
        }
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
