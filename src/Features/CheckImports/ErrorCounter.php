<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

class ErrorCounter
{
    /**
     * @var array<string, array>
     */
    public static $errors;

    public static function getWrongCount(): int
    {
        return self::getCount('extraWrongImport');
    }

    public static function getWrongUsedClassCount(): int
    {
        return self::getCount('wrongClassRef');
    }

    public static function getExtraImportsCount(): int
    {
        return self::getCount('extraCorrectImport') + self::getWrongCount();
    }

    public static function getTotalErrors(): int
    {
        return self::getWrongCount() + self::getWrongUsedClassCount() + self::getExtraImportsCount();
    }

    private static function getCount(string $key)
    {
        return count(self::$errors[$key] ?? []);
    }
}
