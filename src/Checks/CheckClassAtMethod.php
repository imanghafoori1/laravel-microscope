<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckClassAtMethod
{
    public static function check($tokens, $absFilePath)
    {
        event('laravel_microscope.checking_file', [$absFilePath]);

        if (self::checkAtSignStrings($tokens, $absFilePath)) {
            return token_get_all(file_get_contents($absFilePath));
        }
    }

    public static function checkAtSignStrings($tokens, $absFilePath, $onlyAbsClassPath = false)
    {
        return self::handleAtSignTokens(
            $absFilePath,
            self::getAtSignTokens($tokens, $onlyAbsClassPath)
        );
    }

    public static function getAtSignTokens($tokens, $onlyAbsClassPath)
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

    public static function handleAtSignTokens($absFilePath, $atSignTokens)
    {
        $fix = false;
        $printer = app(ErrorPrinter::class);

        foreach ($atSignTokens as $token) {
            $trimmed = \trim($token[1], '\'\"');
            [$class, $method] = \explode('@', $trimmed);

            $class = str_replace('\\\\', '\\', $class);

            if (! \class_exists($class)) {
                $isInUserSpace = Analyzers\Fixer::isInUserSpace($class);

                $result = [false];
                if ($isInUserSpace) {
                    $result = Analyzers\Fixer::fixReference($absFilePath, $class, $token[2]);
                }

                if ($result[0]) {
                    $fix = true;
                    $printer->printFixation($absFilePath, $class, $token[2], $result[1]);
                } else {
                    $printer->wrongUsedClassError($absFilePath, $token[1], $token[2]);
                }
            } elseif (! \method_exists($class, $method)) {
                $printer->wrongMethodError($absFilePath, $trimmed, $token[2]);
            }
        }

        return $fix;
    }
}
