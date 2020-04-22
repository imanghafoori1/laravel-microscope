<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Analyzers\FunctionCall;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;

class RoutePaths
{
    public static function get()
    {
        $routePaths = [];

        foreach (app('router')->routePaths as $path) {
            $routePaths[] = FilePath::normalize($path);
        }

        foreach (config('app.providers') as $providerClass) {
            // we exclude the core or package service providers here.
            if (! Str::contains($providerClass, array_keys(ComposerJson::readKey('autoload.psr-4')))) {
                continue;
            }

            // get tokens by class name
            $path = NamespaceCorrector::getRelativePathFromNamespace($providerClass);

            $providerTokens = token_get_all(file_get_contents(base_path($path).'.php'));

            $methodCalls = [];
            foreach($providerTokens as $i => $routeFileToken) {
                FunctionCall::isMethodCallOnThis('loadRoutesFrom', $providerTokens, $i)
                &&
                $methodCalls[] = FunctionCall::readParameters($providerTokens, $i);
            }

            foreach ($methodCalls as $calls) {
                $routePaths[] = self::fullPath($calls, $providerClass, $path);
            }
        }

        return $routePaths;
    }

    private static function fullPath($calls, $providerClass, $path)
    {
        $path1 = '';
        foreach ($calls[0] as $token) {
            if ($token[0] == T_DIR) {
                // remove class name from the end of string.
                $relativeDir = trim(str_replace(class_basename($providerClass), '', $path), '\\');

                $path1 .= $relativeDir;
            } elseif ($token[0] == T_CONSTANT_ENCAPSED_STRING) {
                $firstParam = trim($token[1], '\'\"');
                $path1 .= $firstParam;
            }
        }

        return FilePath::normalize(base_path($path1));
    }
}
