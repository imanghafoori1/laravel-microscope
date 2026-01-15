<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraImports;

use JetBrains\PhpStorm\Pure;

class ErrorCounter
{
    public static function calculateErrors($errorsList)
    {
        self::$errors['extraImports'] = count($errorsList['extraImports'] ?? []);
    }

    /**
     * @var array<string, array>
     */
    public static $errors = [];

    #[Pure(true)]
    public static function getExtraImportsCount(): int
    {
        return self::getCount('extraImports');
    }

    #[Pure(true)]
    public static function getTotalErrors(): int
    {
        return self::getExtraImportsCount();
    }

    #[Pure(true)]
    private static function getCount(string $key)
    {
        return self::$errors[$key] ?? 0;
    }
}
