<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\TokenAnalyzer\FunctionCall;
use Throwable;

class RoutePaths
{
    /**
     * @return \Generator
     */
    public static function get()
    {
        foreach (app('router')->routePaths as $path) {
            yield FilePath::normalize($path);
        }
        $autoloads = ComposerJson::readAutoload();
        foreach (config('app.providers') as $providerClass) {
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

        foreach (config('microscope.additional_route_files', []) as $routeFilePath) {
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
                $relativeDirPath = \trim(Str::replaceLast(class_basename($providerClass), '', $path), '\\');

                $fullPath .= $relativeDirPath;
            } elseif ($token[0] == T_CONSTANT_ENCAPSED_STRING) {
                $firstParam = \trim($token[1], '\'\"');
                $fullPath .= $firstParam;
            }
        }

        return FilePath::normalize(base_path($fullPath));
    }

    private static function readLoadedRouteFiles($path)
    {
        $tokens = token_get_all(file_get_contents(base_path($path).'.php'));

        foreach ($tokens as $i => $routeFileToken) {
            if (FunctionCall::isMethodCallOnThis('loadRoutesFrom', $tokens, $i)) {
                yield FunctionCall::readParameters($tokens, $i);
            }
        }
    }
}
