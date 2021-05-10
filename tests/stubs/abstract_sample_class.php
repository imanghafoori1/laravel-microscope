<?php

abstract class abstract_sample
{
    // simple methods
    abstract function abstract_1();
    abstract public function abstract_2();
    abstract protected function abstract_3();

    //static methods
    abstract static function abstract_static_2();
    abstract public static function abstract_static_1();
    abstract protected static function abstract_static_3();

    // methods with returns type
    abstract function abstract_with_return_type_1(): test;
    abstract function abstract_with_return_type_2(): string;
    abstract function abstract_with_return_type_3(): bool;
    abstract function abstract_with_return_type_4(): int;
    abstract function abstract_with_return_type_5(): array;
    abstract function abstract_with_return_type_6(): void;
    abstract function abstract_with_return_type_7(): float;
}
