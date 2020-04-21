<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Analyzers\MethodParser;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;

class RoutePaths
{
    public static function get()
    {
        $routePaths = [];

        foreach (config('app.providers') as $providerClass) {
            // we exclude the core or package service providers here.
            if (! Str::contains($providerClass, array_keys(ComposerJson::readKey('autoload.psr-4')))) {
                continue;
            }

            // get tokens by class name
            $path = NamespaceCorrector::getRelativePathFromNamespace($providerClass);
            $routeFileTokens = token_get_all(file_get_contents(base_path($path).'.php'));

            $methodCalls = MethodParser::extractParametersValue($routeFileTokens, ['loadRoutesFrom']);

            foreach ($methodCalls as $calls) {
                $firstParam = str_replace(["'", '"'], '', $calls['params'][0]);

                // remove class name from the end of string.
                $dir = trim(str_replace(class_basename($providerClass), '', $path), '\\');

                $firstParam = str_replace('__DIR__.', $dir, $firstParam);

                $routePaths[] = FilePath::normalize(base_path($firstParam));
            }
        }

        return $routePaths;
    }
}
