<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckClassyStrings\Checks;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\Str;

class ClassyStringsCheck implements Check
{
    use CachedCheck;

    public static $cacheKey = 'stringy_classes';

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor  $file
     * @return bool
     */
    public static function performCheck(PhpFileDescriptor $file)
    {
        $hasError = false;
        foreach (ComposerJson::readPsr4() as $psr4) {
            $strings = self::getHardCodedStrings($file->getTokens());
            $tokens = self::getStringyClasses($strings, array_keys($psr4));

            if (ClassyStringProcessor::process($tokens, $file)) {
                $hasError = true;
            }
        }

        return $hasError;
    }

    /**
     * @param  string[]  $namespaces
     * @param  string  $classPath
     * @return bool
     */
    private static function isPossiblyClassyString($namespaces, $classPath)
    {
        $chars = ['@', ' ', ',', ':', '/', '.', '-', '\'', '"', '\\\\'];

        return Str::contains($classPath, $namespaces) &&
            ! in_array($classPath, $namespaces) &&
            ! Str::contains($classPath, $chars) &&
            ! Str::endsWith($classPath, '\\');
    }

    /**
     * @param  string  $string
     * @return string
     */
    private static function rectify($string)
    {
        $classPath = trim($string, $string[0] === "'" ? "'" : '"');
        $classPath = str_replace('\\\\', '\\', $classPath);

        return $classPath;
    }

    private static function getHardCodedStrings($tokens)
    {
        foreach ($tokens as $token) {
            if ($token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                continue;
            }

            yield $token;
        }
    }

    private static function getStringyClasses($strings, array $namespaces)
    {
        foreach ($strings as $token) {
            $classPath = self::rectify($token[1]);

            if (! self::isPossiblyClassyString($namespaces, $classPath)) {
                continue;
            }

            yield [$token, $classPath];
        }
    }
}
