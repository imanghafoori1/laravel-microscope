<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckImports;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\CheckImportReporter;
use PHPUnit\Framework\TestCase;

class CheckImportReporterTest extends TestCase
{
    public function testFormatComposerPathWithEmptyPath()
    {
        $result = CheckImportReporter::formatComposerPath('');
        $this->assertEquals(' <fg=blue>./composer.json</>', $result);

        $result = CheckImportReporter::formatComposerPath('/');
        $this->assertEquals(' <fg=blue>./composer.json</>', $result);
    }

    public function testFormatComposerPathWithLeadingSlash()
    {
        $result = CheckImportReporter::formatComposerPath('/path/to/composer');
        $this->assertEquals(' <fg=blue>./path/to/composer/composer.json</>', $result);
    }

    public function testFormatComposerPathWithTrailingSlash()
    {
        $result = CheckImportReporter::formatComposerPath('path/to/composer/');
        $this->assertEquals(' <fg=blue>./path/to/composer/composer.json</>', $result);
    }

    public function testFormatComposerPathWithBothLeadingAndTrailingSlashes()
    {
        $result = CheckImportReporter::formatComposerPath('/path/to/composer/');
        $this->assertEquals(' <fg=blue>./path/to/composer/composer.json</>', $result);
    }

    public function testFormatComposerPathWithoutSlashes()
    {
        $result = CheckImportReporter::formatComposerPath('path/to/composer');
        $this->assertEquals(' <fg=blue>./path/to/composer/composer.json</>', $result);
    }
}

