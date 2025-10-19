<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use PHPUnit\Framework\TestCase;

class BaseTestClass extends TestCase
{
    use CallsPrivateMethods;

    /**
     * get tokens of stubs classes.
     *
     * @param  string  $path  path of stub file
     * @return array
     */
    protected function getTokens(string $path): array
    {
        return token_get_all(file_get_contents(__DIR__.$path));
    }
}
