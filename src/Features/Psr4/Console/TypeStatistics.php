<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\Console;

class TypeStatistics
{
    public $namespaceCount = [];

    public $enum = 0;

    public $interface = 0;

    public $class = 0;

    public $trait = 0;

    public function increment(?string $type)
    {
        $type && $this->$type++;
    }

    public function iterate($callback)
    {
        return array_map(
            fn ($typeStr) => $callback($typeStr, (int) $this->$typeStr),
            ['class', 'trait', 'interface', 'enum']
        );
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
