<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckImports;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use PHPUnit\Framework\TestCase;

class CheckImportReporterTest extends TestCase
{
    public function testFormatComposerPathWithEmptyPath()
    {
        $result = Psr4Report::formatComposerPath('');
        $this->assertEquals(' <fg=blue>./composer.json</>', $result);

        $result = Psr4Report::formatComposerPath('/');
        $this->assertEquals(' <fg=blue>./composer.json</>', $result);
    }

    public function testFormatComposerPathWithLeadingSlash()
    {
        $result = Psr4Report::formatComposerPath('/path/to/composer');
        $this->assertEquals(' <fg=blue>./path/to/composer/composer.json</>', $result);
    }

    public function testFormatComposerPathWithTrailingSlash()
    {
        $result = Psr4Report::formatComposerPath('path/to/composer/');
        $this->assertEquals(' <fg=blue>./path/to/composer/composer.json</>', $result);
    }

    public function testFormatComposerPathWithBothLeadingAndTrailingSlashes()
    {
        $result = Psr4Report::formatComposerPath('/path/to/composer/');
        $this->assertEquals(' <fg=blue>./path/to/composer/composer.json</>', $result);
    }

    public function testFormatComposerPathWithoutSlashes()
    {
        $result = Psr4Report::formatComposerPath('path/to/composer');
        $this->assertEquals(' <fg=blue>./path/to/composer/composer.json</>', $result);
    }
}
