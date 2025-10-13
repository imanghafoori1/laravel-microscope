<?php

namespace Imanghafoori\LaravelMicroscope\Tests\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\Iterator;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedFiles;
use PHPUnit\Framework\TestCase;

class ForAutoloadedClassMapsTest extends TestCase
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
                public function readAutoloadClassMap()
                {
                    return ['app'];
                }
            };
        };
        $iterator = new Iterator(CheckSet::initParams(new class
        {
            public static function check($file)
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

        $iterator->printAll($iterator->forClassmaps());
        $this->assertIsArray($_SESSION['msg']);
        $this->assertCount(3, $_SESSION['msg']);
    }

    public function test_basic2()
    {
        ComposerJson::$composer = function () {
            return new class
            {
                public function autoloadedFilesList()
                {
                    return ['app.php'];
                }
            };
        };

        $checkSet = CheckSet::initParams(new class
        {
            public static function check($file)
            {
            }
        }, new class
        {
            public function option()
            {
                return '';
            }
        });

        $iterator = new Iterator($checkSet, new class
        {
            public function write($msg)
            {
                $_SESSION['msg'][] = $msg;
            }
        });

        $iterator->printAll(ForAutoloadedFiles::check($checkSet));
        $this->assertIsArray($_SESSION['msg']);
        $this->assertCount(4, $_SESSION['msg']);
    }
}
