<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\ClassAtMethodHandler;

class CheckClassAtMethod
{
    public static $handler = ClassAtMethodHandler::class;

    public static function check($tokens, $absFilePath)
    {
        $replaced = self::$handler::handle(
            $absFilePath,
            self::getAtSignTokens($tokens, $absFilePath)
        );

        if ($replaced) {
            return token_get_all(file_get_contents($absFilePath));
        }
    }

    private static function getAtSignTokens($tokens, $onlyAbsClassPath)
    {
        $atSignTokens = [];

        foreach ($tokens as $token) {
            // If it is a string containing a single '@'
            if ($token[0] != T_CONSTANT_ENCAPSED_STRING || \substr_count($token[1], '@') != 1) {
                continue;
            }

            $trimmed = \trim($token[1], '\'\"');

            if ($onlyAbsClassPath && $trimmed[0] !== '\\') {
                continue;
            }

            [$class] = \explode('@', $trimmed);

            if (\substr_count($class, '\\') <= 0) {
                continue;
            }

            if (self::contains($trimmed, ['-', '/', '[', '*', '+', '.', '(', '$', '^'])) {
                continue;
            }

            $atSignTokens[] = $token;
        }

        return $atSignTokens;
    }

    private static function contains($haystack, $needles)
    {
        foreach ($needles as $needle) {
            if (mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
