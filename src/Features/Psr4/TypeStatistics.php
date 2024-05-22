<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4;

class TypeStatistics
{
    public $namespaceCount = [];

    public $enum = 0;

    public $interface = 0;

    public $class = 0;

    public $trait = 0;

    public function increment(int $type)
    {
        if ($type === T_INTERFACE) {
            $this->interface++;
        } elseif ($type === T_CLASS) {
            $this->class++;
        } elseif ($type === T_TRAIT) {
            $this->trait++;
        } elseif ($type === T_ENUM) {
            $this->enum++;
        }
    }

    public function iterate($callback)
    {
        $results = [];
        foreach (['class', 'trait', 'interface', 'enum'] as $prop) {
            $results[] = $callback($prop, $this->$prop);
        }

        return $results;
    }

    /**
     * @return void
     */
    public function namespaceFiles($namespace, int $count)
    {
        $this->namespaceCount[$namespace] = $count;
    }

    public function getTotalCount()
    {
        return array_sum($this->namespaceCount);
    }
}
