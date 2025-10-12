<?php

namespace Imanghafoori\LaravelMicroscope\Tests\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\Iterator;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use PHPUnit\Framework\TestCase;

class IteratorTest extends TestCase
{
    public function setUp(): void
    {
        $_SESSION['msg'] = [];
    }

    public function tearDown(): void
    {
        unset($_SESSION['msg']);
    }

    public function test_basic()
    {
        ComposerJson::$composer = function () {
            return new class
            {
                public function readAutoload()
                {
                    return [
                        '/' => ['App\\' => 'app']
                    ];
                }
            };
        };
        $iterator = new Iterator(CheckSet::initParams(new class
        {
            public static function check()
            {

            }
        }, new class
        {
            public function option()
            {
                return '';
            }
        }), new class
        {
            public function write($msg)
            {
                $_SESSION['msg'][] = $msg;
            }
        });

        $iterator->formatPrintPsr4();
        $this->assertIsArray($_SESSION['msg']);
        $this->assertStringContainsString('<fg=blue>./composer.json</>', $_SESSION['msg'][0]);
        $this->assertStringContainsString('App\\', $_SESSION['msg'][2]);
        $this->assertStringContainsString('./app', $_SESSION['msg'][3]);
    }
}