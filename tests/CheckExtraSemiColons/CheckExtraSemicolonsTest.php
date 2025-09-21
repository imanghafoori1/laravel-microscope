<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckExtraSemiColons;

use Imanghafoori\LaravelMicroscope\Commands\CheckExtraSemiColons;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\LaravelMicroscope\SearchReplace\PatternRefactorings;
use Imanghafoori\LaravelMicroscope\SearchReplace\PostReplaceAndSave;
use Imanghafoori\SearchReplace\PatternParser;
use PHPUnit\Framework\TestCase;

class CheckExtraSemicolonsTest extends TestCase
{
    public function setUp(): void
    {
        copy(__DIR__.'/extra-semi-initial.stub', __DIR__.'/extra-semi.temp');
    }

    public function tearDown(): void
    {
        unlink(__DIR__.'/extra-semi.temp');
    }

    public function test_extra_semicolons_get_removed()
    {
        ErrorPrinter::$basePath = __DIR__;
        ErrorPrinter::singleton()->printer = new class
        {
            public function confirm()
            {
                return true;
            }

            public function writeln()
            {
                return '';
            }
        };

        $file = PhpFileDescriptor::make(__DIR__.'/extra-semi.temp');
        $patterns = CheckExtraSemiColons::patterns(false);
        $parsedPatterns = PatternParser::parsePatterns($patterns);

        CachedFiles::$folderPath = __DIR__.'/cache';
        PostReplaceAndSave::$forceSave = true;
        PatternRefactorings::check($file, [$parsedPatterns]);

        $actual = file_get_contents(__DIR__.'/extra-semi.temp');
        $expected = file_get_contents(__DIR__.'/extra-semi-final.stub');

        $this->assertEquals($expected, $actual);
    }
}
