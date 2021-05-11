<?php

abstract class abstract_sample
{
    // simple methods
    abstract public function abstract_1();

    abstract public function abstract_2();

    abstract protected function abstract_3();

    //static methods
    abstract public static function abstract_static_2();

    abstract public static function abstract_static_1();

    abstract protected static function abstract_static_3();

    // methods with returns type
    abstract public function abstract_with_return_type_1(): test;

    abstract public function abstract_with_return_type_2(): string;

    abstract public function abstract_with_return_type_3(): bool;

    abstract public function abstract_with_return_type_4(): int;

    abstract public function abstract_with_return_type_5(): array;

    abstract public function abstract_with_return_type_6(): void;

    abstract public function abstract_with_return_type_7(): float;

    abstract public function abstract_with_return_type_8(): ?string;

    //with parameters
    abstract public function abstract_with_parameter_0($parameter1);

    abstract public function abstract_with_parameter_1(?int $parameter1);

    abstract public function abstract_with_parameter_2(int $parameter1);

    abstract public function abstract_with_parameter_3(int $parameter1, $parameter2, string $parameter3);

    abstract public function abstract_with_parameter_4(...$parameter2);

    abstract public function abstract_with_parameter_5(string ...$parameter1);

    abstract public function abstract_with_parameter_6(?string ...$parameter1);

    abstract public function abstract_with_parameter_7($parameter1 = null);
}
