<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\FunctionCall;
use Throwable;

class RoutePaths
{
    public static $paths = [];

    public static $providers = [];

    public static $additionalFiles = [];

    /**
     * @return \Generator<int, string>
     */
    public static function get()
    {
        foreach (self::$paths as $path) {
            yield FilePath::normalize($path);
        }

        $autoloads = ComposerJson::readPsr4();
        foreach (self::$providers as $providerClass) {
            // we exclude the core or package service providers here.
            foreach ($autoloads as $autoload) {
                if (! Str::contains($providerClass, array_keys($autoload))) {
                    continue 2;
                }
            }

            // get tokens by class name
            $path = ComposerJson::make()->getRelativePathFromNamespace($providerClass);

            try {
                $methodCalls = self::readLoadedRouteFiles($path);
            } catch (Throwable $e) {
                $methodCalls = [];
            }

            foreach ($methodCalls as $calls) {
                $routeFilePath = self::fullPath($calls, $providerClass, $path);
                if (is_file($routeFilePath)) {
                    yield $routeFilePath;
                }
            }
        }

        foreach (self::$additionalFiles as $routeFilePath) {
            if (is_file($routeFilePath)) {
                yield $routeFilePath;
            }
        }
    }

    private static function fullPath($calls, $providerClass, $path)
    {
        $fullPath = '';

        foreach ($calls[0] as $token) {
            if ($token[0] == T_DIR) {
                // remove class name from the end of string.
                $relativeDirPath = trim(Str::replaceLast(self::className($providerClass), '', $path), '\\/');

                $fullPath .= $relativeDirPath;
            } elseif ($token[0] == T_CONSTANT_ENCAPSED_STRING) {
                $firstParam = trim($token[1], '\'\"');
                $fullPath .= $firstParam;
            }
        }

        return FilePath::normalize(BasePath::$path.DIRECTORY_SEPARATOR.$fullPath);
    }

    private static function readLoadedRouteFiles($path)
    {
        $tokens = PhpFileDescriptor::make(BasePath::$path.DIRECTORY_SEPARATOR.$path.'.php')->getTokens();

        foreach ($tokens as $i => $routeFileToken) {
            if (FunctionCall::isMethodCallOnThis('loadRoutesFrom', $tokens, $i)) {
                yield FunctionCall::readParameters($tokens, $i);
            }
        }
    }

    private static function className($class)
    {
        return basename(str_replace('\\', '/', $class));
    }
}
