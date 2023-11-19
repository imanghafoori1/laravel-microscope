<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\ClassAtMethod;

class CheckClassAtMethod
{
    public static $handler = ClassAtMethod::class;

    public static function check($tokens, $absFilePath)
    {
        if (self::checkAtSignStrings($tokens, $absFilePath)) {
            return token_get_all(file_get_contents($absFilePath));
        }
    }

    public static function checkAtSignStrings($tokens, $absFilePath, $onlyAbsClassPath = false)
    {
        return self::$handler::handle(
            $absFilePath,
            self::getAtSignTokens($tokens, $onlyAbsClassPath)
        );
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

            if (Str::contains($trimmed, ['-', '/', '[', '*', '+', '.', '(', '$', '^'])) {
                continue;
            }

            $atSignTokens[] = $token;
        }

        return $atSignTokens;
    }
}
