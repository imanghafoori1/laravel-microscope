<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use JetBrains\PhpStorm\Pure;

class ErrorCounter
{
    public static function calculateErrors($errors)
    {
        self::$errors['extraWrongImport'] = count($errors['extraWrongImport'] ?? []);
        self::$errors['wrongClassRef'] = count($errors['wrongClassRef'] ?? []);
    }

    /**
     * @var array<string, array>
     */
    public static $errors = [];

    #[Pure(true)]
    public static function getExtraWrongCount(): int
    {
        return self::getCount('extraWrongImport');
    }

    #[Pure(true)]
    public static function getWrongUsedClassCount(): int
    {
        return self::getCount('wrongClassRef');
    }

    #[Pure(true)]
    public static function getExtraImportsCount(): int
    {
        return self::getExtraWrongCount();
    }

    #[Pure(true)]
    public static function getTotalErrors(): int
    {
        return self::getExtraWrongCount() + self::getWrongUsedClassCount() + self::getExtraImportsCount();
    }

    #[Pure(true)]
    private static function getCount(string $key)
    {
        return self::$errors[$key] ?? 0;
    }
}
