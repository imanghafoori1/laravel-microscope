<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckView;

use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckView\Check\CheckView;
use Imanghafoori\LaravelMicroscope\Features\CheckView\Check\CheckViewStats;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use PHPUnit\Framework\TestCase;

class CheckViewTest extends TestCase
{
    public function testCheckView()
    {
        View::swap(new class
        {
            public function exists()
            {
                return false;
            }
        });
        $file = PhpFileDescriptor::make(__DIR__.'/check-view.stub');
        $result = CheckView::performCheck($file);
        $this->assertTrue($result);
        $pendingError = ErrorPrinter::singleton()->errorsList['missing_view'][0];
        $this->assertStringContainsString(
            'sdcasdc.blade.php',
            $pendingError->getErrorData()
        );

        $this->assertEquals(1, CheckViewStats::$checkedCallsCount);
        $this->assertEquals(0, CheckViewStats::$skippedCallsCount);
    }
}
