<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckEnvCalls;

use Generator;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\FunctionCall;
use Imanghafoori\TokenAnalyzer\TokenManager;

class EnvCallsCheck implements Check
{
    use CachedCheck;

    /**
     * @var class-string
     */
    public static $onErrorCallback = EnvCallHandler::class;

    /**
     * @var string
     */
    private static $cacheKey = 'env_calls_command';

    public static function performCheck(PhpFileDescriptor $file)
    {
        if (self::isLikelyConfigFile($file)) {
            return false;
        }

        return self::handleErrors($file, self::getErrors($file));
    }

    private static function getErrors(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();

        foreach ($tokens as $i => $token) {
            if (strtolower($token[1] ?? '') === 'env') {
                $tokens[$i][1] = 'env';
                continue;
            }

            $index = FunctionCall::isGlobalCall('env', $tokens, $i);
            if (! $index) {
                continue;
            }

            yield $index;
        }
    }

    private static function isLikelyConfigFile(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();
        [$token] = TokenManager::getNextToken($tokens, 0);

        if ($token[0] === T_NAMESPACE) {
            return false;
        }

        if ($token[0] === T_RETURN && stripos($file->getAbsolutePath(), 'config')) {
            return true;
        }

        return $file->getFileName() === 'config.php';
    }

    private static function handleErrors(PhpFileDescriptor $file, Generator $indexes): bool
    {
        $tokens = $file->getTokens();

        foreach ($indexes as $index) {
            self::$onErrorCallback::handle(
                $file,
                $tokens[$index][1],
                $tokens[$index][2]
            );
        }

        return isset($index);
    }
}
