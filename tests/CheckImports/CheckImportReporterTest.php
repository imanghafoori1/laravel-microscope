<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckImports;

use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\Reports\ComposerJsonReport;
use PHPUnit\Framework\TestCase;

class CheckImportReporterTest extends TestCase
{
    public function setUp(): void
    {
        Color::$color = false;
    }

    public function tearDown(): void
    {
        Color::$color = true;
    }

    public function testFormatComposerPathWithEmptyPath()
    {
        $result = ComposerJsonReport::formatComposerPath('');
        $this->assertEquals(' ./composer.json', $result);

        $result = ComposerJsonReport::formatComposerPath('/');
        $this->assertEquals(' ./composer.json', $result);
    }

    public function testFormatComposerPathWithLeadingSlash()
    {
        $result = ComposerJsonReport::formatComposerPath('/path/to/composer');
        $this->assertEquals(' ./path/to/composer/composer.json', $result);
    }

    public function testFormatComposerPathWithTrailingSlash()
    {
        $result = ComposerJsonReport::formatComposerPath('path/to/composer/');
        $this->assertEquals(' ./path/to/composer/composer.json', $result);
    }

    public function testFormatComposerPathWithBothLeadingAndTrailingSlashes()
    {
        $result = ComposerJsonReport::formatComposerPath('/path/to/composer/');
        $this->assertEquals(' ./path/to/composer/composer.json', $result);
    }

    public function testFormatComposerPathWithoutSlashes()
    {
        $result = ComposerJsonReport::formatComposerPath('path/to/composer');
        $this->assertEquals(' ./path/to/composer/composer.json', $result);
    }
}
