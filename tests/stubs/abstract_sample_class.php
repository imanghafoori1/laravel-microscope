<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Orchestra\Testbench\TestCase;

abstract class abstract_sample extends TestCase
{
    public static $lastToken = [null, null, null];

    private $secLastToken;

    public $token = [null, null, null];

    // simple methods
    abstract public function abstract_0();

    abstract public function abstract_1();

    private static $secLastToken2;

    abstract protected function abstract_2();

    // static methods
    abstract public static function abstract_static_3();

    abstract public static function abstract_static_4();

    abstract protected static function abstract_static_5();

    // methods with returns type
    abstract public function abstract_with_return_type_6(): test;

    abstract public function abstract_with_return_type_7(): string;

    abstract public function abstract_with_return_type_8(): bool;

    abstract public function abstract_with_return_type_9(): int;

    abstract public function abstract_with_return_type_10(): array;

    abstract public function abstract_with_return_type_11(): void;

    abstract public function abstract_with_return_type_12(): float;

    abstract public function abstract_with_return_type_13(): ?string;

    // with parameters
    abstract public function abstract_with_parameter_14($parameter1);

    abstract public function abstract_with_parameter_15(?int $parameter1);

    abstract public function abstract_with_parameter_16(int $parameter1);

    abstract public function abstract_with_parameter_17(int $parameter1, $parameter2, string $parameter3);

    abstract public function abstract_with_parameter_18(...$parameter2);

    abstract public function abstract_with_parameter_19(string ...$parameter1);

    abstract public function abstract_with_parameter_20(?string ...$parameter1);

    abstract public function abstract_with_parameter_21($parameter1 = null);

    abstract public function abstract_with_parameter_22();

    abstract public function abstract_with_parameter_23();

    abstract protected function abstract_with_parameter_24();

    abstract public static function abstract_static_25();

    public function hello4()
    {
    }
}
