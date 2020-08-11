<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use Illuminate\Database\Eloquent\Factory;

class SpyFactory extends Factory
{
    public $loadedPaths = [];

    public function load($path)
    {
        if (is_dir($path)) {
            $this->loadedPaths[] = $path;
        } else {
            // throw some warning.
        }

        return parent::load($path);
    }
}
