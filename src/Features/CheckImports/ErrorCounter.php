<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

class ErrorCounter
{
    /**
     * @var array<string, array>
     */
    public static $errors;

    public static function getExtraWrongCount(): int
    {
        return self::getCount('extraWrongImport');
    }

    public static function getWrongUsedClassCount(): int
    {
        return self::getCount('wrongClassRef');
    }

    public static function getExtraImportsCount(): int
    {
        return self::getCount('extraCorrectImport') + self::getExtraWrongCount();
    }

    public static function getTotalErrors(): int
    {
        return self::getExtraWrongCount() + self::getWrongUsedClassCount() + self::getExtraImportsCount();
    }

    private static function getCount(string $key)
    {
        return self::$errors[$key] ?? 0;
    }
}
