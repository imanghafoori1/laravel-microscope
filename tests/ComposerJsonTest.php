<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;

class ComposerJsonTest extends BaseTestClass
{
    /** @test */
    public function read_autoload()
    {
        ComposerJson::$composerPath = __DIR__.'/stubs/composer_json';

        $expected = [
            'a2' => [
                'G2\\' => 'a2/ref/',
                'App2\\' => 'a2/app2/',
                'Imanghafoori\LaravelMicroscope\Tests\\' => 'a2/tests/',
            ],
            '/' => [
                'App\\' => 'app/',
                'Imanghafoori\LaravelMicroscope\Tests\\' => 'tests/',
            ],
        ];

        $this->assertEquals($expected, ComposerJson::readAutoload());
        ComposerJson::$composerPath = null;
    }
}
