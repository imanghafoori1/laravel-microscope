<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4;

class TypeStatistics
{
    public $namespaceCount = [];

    public $enum = 0;

    public $interface = 0;

    public $class = 0;

    public $trait = 0;

    public function increment(string $type)
    {
        if ($type === 'interface') {
            $this->interface++;
        } elseif ($type === 'class') {
            $this->class++;
        } elseif ($type === 'trait') {
            $this->trait++;
        } elseif ($type === 'enum') {
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
