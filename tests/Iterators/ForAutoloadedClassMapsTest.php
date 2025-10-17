<?php

namespace Imanghafoori\LaravelMicroscope\Tests\Iterators;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\Iterator;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedFiles;
use PHPUnit\Framework\TestCase;

class ForAutoloadedClassMapsTest extends TestCase
{
    public function setUp(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        BasePath::$path = __DIR__;
        $dir = __DIR__.$ds.'app'.$ds;
        copy($dir.'MyClass.stub', $dir.'MyClass.php');
        $_SESSION['file'] = [];
        $_SESSION['msg'] = [];
    }

    public function tearDown(): void
    {
        $ds = DIRECTORY_SEPARATOR;

        BasePath::$path = '';
        unlink(__DIR__.$ds.'app'.$ds.'MyClass.php');

        unset($_SESSION['file']);
        unset($_SESSION['msg']);
    }

    public function test_forClassmaps()
    {
        ComposerJson::$composer = function () {
            return new class
            {
                public function readAutoloadClassMap()
                {
                    return [
                        '/' => ['app']
                    ];
                }
            };
        };
        $iterator = new Iterator(CheckSet::initParams([new class
        {
            public static function check(PhpFileDescriptor $file)
            {
                $_SESSION['file'][] = $file->getFileName();
            }
        }], new class
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
        $this->assertEquals('MyClass.php', $_SESSION['file'][0]);
        $this->assertCount(6, $_SESSION['msg']);
    }

    public function test_for_autoloaded_files()
    {
        ComposerJson::$composer = function () {
            return new class
            {
                public function autoloadedFilesList()
                {
                    return [
                        '/' => ['app/MyClass.php']
                    ];
                }
            };
        };

        $checkSet = CheckSet::initParams([new class
        {
            public static function check(PhpFileDescriptor $file)
            {
                $_SESSION['file'][] = $file->getFileName();
            }
        }], new class
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
        $this->assertCount(5, $_SESSION['msg']);
        $this->assertEquals(['MyClass.php'], $_SESSION['file']);
    }
}
