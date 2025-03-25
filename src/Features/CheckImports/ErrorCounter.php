<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use JetBrains\PhpStorm\Pure;

class ErrorCounter
{
    /**
     * @var array<string, array>
     */
    public static $errors;

    #[Pure]
    public static function getExtraWrongCount(): int
    {
        return self::getCount('extraWrongImport');
    }

    #[Pure]
    public static function getWrongUsedClassCount(): int
    {
        return self::getCount('wrongClassRef');
    }

    #[Pure]
    public static function getExtraImportsCount(): int
    {
        return self::getCount('extraCorrectImport') + self::getExtraWrongCount();
    }

    #[Pure]
    public static function getTotalErrors(): int
    {
        return self::getExtraWrongCount() + self::getWrongUsedClassCount() + self::getExtraImportsCount();
    }

    #[Pure]
    private static function getCount(string $key)
    {
        return self::$errors[$key] ?? 0;
    }
}
