<?php

namespace Imanghafoori\LaravelMicroscope\Tests\Iterators;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\BladeReport;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use PHPUnit\Framework\TestCase;

class ForBladeFilesTest extends TestCase
{
    public function setUp(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $dir = __DIR__.$ds.'views';
        mkdir($dir);
        copy($dir.$ds.'a.blade', $dir.$ds.'a.blade.php');
        copy($dir.$ds.'b.blade', $dir.$ds.'b.blade.php');
    }

    public function tearDown(): void
    {
        $ds = DIRECTORY_SEPARATOR;
        $dir = __DIR__.$ds.'views';
        rmdir($dir);
        unlink($dir.$ds.'a.blade.php');
        unlink($dir.$ds.'b.blade.php');
    }

    public function test_basic()
    {
        ForBladeFiles::$paths = ['hint_1' => [__DIR__.DIRECTORY_SEPARATOR.'views']];
        $DTOs = ForBladeFiles::check(CheckSet::init([
            new class
            {
                public static function check($file)
                {
                    //
                }
            }
        ], PathFilterDTO::make('a')));

        $report = BladeReport::getBladeStats($DTOs);
        $this->assertIsScalar($report);
        $this->assertStringContainsString('tests/Iterators/views', $report);
        $this->assertStringContainsString('1 file', $report);
    }
}