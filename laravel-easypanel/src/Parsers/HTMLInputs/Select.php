<?php

namespace EasyPanel\Parsers\HTMLInputs;

use \Exception;

class Select extends BaseInput
{
    protected $stub = 'select.stub';

    public function dataProvider($array)
    {
        $this->stub = 'select-provider.stub';

        [$class, $method] = $array;

        if (! class_exists($class)){
            throw new Exception("Class {$class} doesn't exist.");
        }

        $method = $method ?: 'handle';

        if (! method_exists($class, $method)){
            throw new Exception("Method {$method} doesn't exist on {$class} class.");
        }

        $this->provider = "\\{$class}::{$method}()";

        return $this;
    }
}
