<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CachedFileCheckTrait;

use Imanghafoori\LaravelMicroscope\Features\SearchReplace\CachedFiles;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CachedCheckTest extends TestCase
{
    public function setUp(): void
    {
        CachedFiles::$folderPath = __DIR__.DIRECTORY_SEPARATOR;
        $file = __DIR__.DIRECTORY_SEPARATOR.'my_key_1.php';
        file_exists($file) && unlink($file);
        $_SERVER['count'] = 0;
    }

    public function tearDown(): void
    {
        $file = __DIR__.DIRECTORY_SEPARATOR.'my_key_1.php';
        file_exists($file) && unlink($file);
        unset($_SERVER['count']);
    }

    #[Test]
    public function test_check()
    {
        $obj = new class
        {
            use CachedCheck;

            public static $cacheKey = 'my_key_1';

            public static function performCheck(PhpFileDescriptor $file)
            {
                $_SERVER['count']++;

                return false;
            }
        };

        $obj::check(PhpFileDescriptor::make(__DIR__.DIRECTORY_SEPARATOR.'SampleFile.stub'));
        CachedFiles::writeCacheFiles();

        $this->assertFileExists(__DIR__.DIRECTORY_SEPARATOR.'my_key_1.php');
        $array = require __DIR__.DIRECTORY_SEPARATOR.'my_key_1.php';

        $this->assertIsArray($array);
        $this->assertCount(1, $array);
        foreach ($array as $k => $v) {
            $this->assertEquals('SampleFile.stub', $v);
            $this->assertIsString($k);
        }

        $this->assertEquals(1, $_SERVER['count']);

        $obj::check(PhpFileDescriptor::make(__DIR__.DIRECTORY_SEPARATOR.'SampleFile.stub'));

        $this->assertEquals(1, $_SERVER['count']);
    }
}
