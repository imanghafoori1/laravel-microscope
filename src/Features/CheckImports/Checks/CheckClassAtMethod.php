<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\ClassAtMethodHandler;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use JetBrains\PhpStorm\Pure;

class CheckClassAtMethod implements Check
{
    public static $handler = ClassAtMethodHandler::class;

    public static function check(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();
        $absFilePath = $file->getAbsolutePath();

        $replaced = self::$handler::handle(
            $file,
            self::getAtSignTokens($tokens, $absFilePath)
        );

        if ($replaced) {
            return $file->getTokens(true);
        }
    }

    #[Pure]
    private static function getAtSignTokens($tokens, $onlyAbsClassPath)
    {
        $atSignTokens = [];

        foreach ($tokens as $token) {
            // If it is a string containing a single '@'
            if ($token[0] != T_CONSTANT_ENCAPSED_STRING || substr_count($token[1], '@') != 1) {
                continue;
            }

            $trimmed = trim($token[1], '\'\"');

            if ($onlyAbsClassPath && $trimmed[0] !== '\\') {
                continue;
            }

            [$class] = explode('@', $trimmed);

            if (substr_count($class, '\\') <= 0) {
                continue;
            }

            if (self::contains($trimmed, ['-', '/', '[', '*', '+', '.', '(', '$', '^'])) {
                continue;
            }

            $atSignTokens[] = $token;
        }

        return $atSignTokens;
    }

    #[Pure]
    private static function contains($haystack, $needles)
    {
        return Loop::any($needles, fn ($needle) => mb_strpos($haystack, $needle) !== false);
    }
}
