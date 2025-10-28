<?php

namespace Imanghafoori\LaravelMicroscope\Tests\Iterators;

use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\Iterator;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use PHPUnit\Framework\TestCase;

class ForBladeFilesTest extends TestCase
{
    public function setUp(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $dir = __DIR__.$ds.'views';

        BasePath::$path = __DIR__;
        ForBladeFiles::$paths = [
            'hint1' => [__DIR__.'/views'],
        ];
        mkdir($dir.$ds.'a');
        copy($dir.$ds.'a.blade', $dir.$ds.'a'.$ds.'a.blade.php');
        copy($dir.$ds.'b.blade', $dir.$ds.'b.blade.php');

        $_SESSION['msg'] = [];
    }

    public function tearDown(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $dir = __DIR__.$ds.'views';

        unlink($dir.$ds.'a'.$ds.'a.blade.php');
        unlink($dir.$ds.'b.blade.php');
        rmdir($dir.$ds.'a');
        unset($_SESSION['msg']);
    }

    public function test_forBladeFiles()
    {
        $iterator = new Iterator(CheckSet::initParams([new class
        {
            public static function check(PhpFileDescriptor $file)
            {
                $file->getFileName();
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

        $report = $iterator->forBladeFiles();
        $this->assertStringContainsString('blade', $report);
        $this->assertStringContainsString('views', $report);
        $this->assertStringContainsString('2 file', $report);
    }
}
