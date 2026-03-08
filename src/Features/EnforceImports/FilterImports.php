<?php

namespace Imanghafoori\LaravelMicroscope\Features\EnforceImports;

use Imanghafoori\LaravelMicroscope\Foundations\Loop;

class FilterImports
{
    public static function refs($classRefs, $imports, $namespace)
    {
        foreach ($classRefs as $classRef) {
            if (! self::shouldBeImported($classRef['class'], $imports, $namespace)) {
                continue;
            }

            EnforceImportsCheck::$hasError = true;

            yield $classRef;
        }
    }

    private static function shouldBeImported($class, $imports, $namespace)
    {
        if ($class[0] !== '\\') {
            return false;
        }

        if (self::isDirectlyImported($class, $imports)) {
            return false;
        } elseif ($namespace && self::isInSameNamespace($namespace, $class)) {
            return false;
        } else {
            $imports2 = self::restructureImports($imports);
            if (isset($imports2[ltrim($class)])) {
                return false;
            }
        }

        return true;
    }

    private static function isDirectlyImported($class, $imports): bool
    {
        return isset($imports[self::className($class)]);
    }

    private static function restructureImports(array $imports): array
    {
        return Loop::mapKey(
            $imports,
            fn ($import, $key) => [
                '\\'.$import[0] => [$import[1], $key],
            ]
        );
    }

    private static function className($class)
    {
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        return basename($class);
    }

    private static function isInSameNamespace($namespace, $ref)
    {
        return trim(self::beforeLast($ref, '\\'), '\\') === $namespace;
    }

    private static function beforeLast($subject, $search)
    {
        $pos = mb_strrpos($subject, $search) ?: 0;

        return mb_substr($subject, 0, $pos, 'UTF-8');
    }
}
