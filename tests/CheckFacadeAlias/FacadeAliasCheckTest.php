<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckFacadeAlias;

use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasesCheck;
use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;
use PHPUnit\Framework\TestCase;

class FacadeAliasCheckTest extends TestCase
{
    public function setUp(): void
    {
        $_SERVER['writeln'] = [];
        BasePath::$path = __DIR__;
        copy(__DIR__.'/facadeAlias-init.stub', __DIR__.'/test.php');
    }

    public function tearDown(): void
    {
        BasePath::$path = null;
        unset($_SERVER['writeln']);
        unlink(__DIR__.'/test.php');
    }

    public function testFacadeAliasCheck()
    {
        $file = PhpFileDescriptor::make(__DIR__.'/test.php');
        FacadeAliasesCheck::$aliases = [
            'DB' => 'App\Facades\DB',
            'Log' => 'App\Facades\Log',
            'Session' => 'App\Facades\Session',
            'Cache' => 'App\Facades\Cache',
            'Auth' => 'App\Facades\Auth',
            'Config' => 'App\Facades\Config',
            'Artisan' => 'App\Facades\Artisan',
        ];
        FacadeAliasesCheck::$command = new class
        {
            public function writeln($msg)
            {
                $_SERVER['writeln'][] = $msg;
            }

            public function confirm()
            {
                return true;
            }
        };

        FacadeAliasesCheck::$importsProvider = function (PhpFileDescriptor $file) {
            $imports = ParseUseStatement::parseUseStatements($file->getTokens());

            return $imports[0] ?: [$imports[1]];
        };

        FacadeAliasesCheck::check($file);

        $actual = file_get_contents(__DIR__.'/test.php');
        $expected = file_get_contents(__DIR__.'/facadeAlias-final.stub');

        $this->assertEquals($expected, $actual);
        $this->assertEquals([
            'at \\test.php:5',
            'at \\test.php:6',
            'at \\test.php:7',
            'at \\test.php:8',
            'at \\test.php:9',
            'at \\test.php:10',
            'at \\test.php:11',
        ], $_SERVER['writeln']);
    }
}
