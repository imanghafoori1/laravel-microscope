<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckImports;

use Imanghafoori\LaravelMicroscope\Foundations\Reports\ComposerJsonReport;
use PHPUnit\Framework\TestCase;

class CheckImportReporterTest extends TestCase
{
    public function testFormatComposerPathWithEmptyPath()
    {
        $result = ComposerJsonReport::formatComposerPath('');
        $this->assertEquals(' <fg=blue>./composer.json</>', $result);

        $result = ComposerJsonReport::formatComposerPath('/');
        $this->assertEquals(' <fg=blue>./composer.json</>', $result);
    }

    public function testFormatComposerPathWithLeadingSlash()
    {
        $result = ComposerJsonReport::formatComposerPath('/path/to/composer');
        $this->assertEquals(' <fg=blue>./path/to/composer/composer.json</>', $result);
    }

    public function testFormatComposerPathWithTrailingSlash()
    {
        $result = ComposerJsonReport::formatComposerPath('path/to/composer/');
        $this->assertEquals(' <fg=blue>./path/to/composer/composer.json</>', $result);
    }

    public function testFormatComposerPathWithBothLeadingAndTrailingSlashes()
    {
        $result = ComposerJsonReport::formatComposerPath('/path/to/composer/');
        $this->assertEquals(' <fg=blue>./path/to/composer/composer.json</>', $result);
    }

    public function testFormatComposerPathWithoutSlashes()
    {
        $result = ComposerJsonReport::formatComposerPath('path/to/composer');
        $this->assertEquals(' <fg=blue>./path/to/composer/composer.json</>', $result);
    }
}
