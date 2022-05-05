<?php


namespace EasyPanel\Parsers\HTMLInputs;


abstract class InputList
{
    const inputClassMap = [
        'password' => Password::class,
        'text' => Text::class,
        'file' => File::class,
        'email' => Email::class,
        'number' => Number::class,
        'textarea' => Textarea::class,
        'select' => Select::class,
        'ckeditor' => Ckeditor::class,
        'checkbox' => Checkbox::class,
        'date' => Date::class,
        'datetime' => DateTime::class,
        'time' => Time::class,
    ];

    public static function get($name)
    {
        if (!key_exists($name, static::inputClassMap)){
            throw new \Exception("The [$name] input type doesn't exist in input list!");
        }

        return static::inputClassMap[$name];
    }
}
