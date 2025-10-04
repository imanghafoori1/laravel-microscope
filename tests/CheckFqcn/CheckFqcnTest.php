<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckFqcn;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckExtraFQCN\ExtraFQCN;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use JetBrains\PhpStorm\Pure;
use PHPUnit\Framework\TestCase;

class CheckFqcnTest extends TestCase
{
    public function setUp(): void
    {
        copy(__DIR__.'/fqcn-initial.stub', __DIR__.'/fqcn.temp');
    }

    public function tearDown(): void
    {
        unlink(__DIR__.'/fqcn.temp');
    }

    public function testFixFile()
    {
        ExtraFQCN::$imports = self::useStatementParser();
        ExtraFQCN::$fix = true;

        $result = ExtraFQCN::performCheck(PhpFileDescriptor::make(__DIR__.'/fqcn.temp'));

        $actual = file_get_contents(__DIR__.'/fqcn.temp');
        $expected = file_get_contents(__DIR__.'/fqcn-expected.stub');
        $this->assertEquals($expected, $actual);
        $this->assertCount(5, ErrorPrinter::singleton()->errorsList['FQCN']);
        $this->assertStringContainsString('\C\E', ErrorPrinter::singleton()->errorsList['FQCN'][0]->getErrorData());
        $this->assertStringContainsString('\C\E', ErrorPrinter::singleton()->errorsList['FQCN'][1]->getErrorData());
        $this->assertStringContainsString('\C\E', ErrorPrinter::singleton()->errorsList['FQCN'][2]->getErrorData());
        $this->assertStringContainsString('\He\R\T\U2', ErrorPrinter::singleton()->errorsList['FQCN'][3]->getErrorData());
        $this->assertStringContainsString('\He\R\T\Hh can be replaced with: G', ErrorPrinter::singleton()->errorsList['FQCN'][4]->getErrorData());
        $this->assertEquals(true, $result);
    }

    #[Pure]
    private static function useStatementParser()
    {
        return function (PhpFileDescriptor $file) {
            return [
                'He\R\T\A' => [
                    'E' => ['C\E', 5],
                    'Hh' => ['H', 6],
                    'G' => ['He\R\T\Hh', 7],
                ],
            ];
        };
    }
}
