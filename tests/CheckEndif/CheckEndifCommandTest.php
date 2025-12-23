<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckEndif;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckEndIf\CheckEndIfCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CheckEndifCommandTest extends TestCase
{
    #[Test]
    public function basic_command()
    {
        ErrorPrinter::singleton()->printer = new class
        {
            public $isConfirmed = 0;

            public function confirm()
            {
                $this->isConfirmed++;

                return true;
            }
        };

        $iterator = new class
        {
            public $line = null;

            public function printAll($lines)
            {
                $this->line = $lines;
            }

            public function forComposerLoadedFiles()
            {
                return 'forComposerLoadedFiles';
            }

            public function forRoutes()
            {
                return 'forRoutes';
            }
        };

        $command = new CheckEndIfCommand();
        $command->handleCommand($iterator);
        $this->assertEquals([
            'forComposerLoadedFiles',
            'forRoutes',
        ], $iterator->line);
    }
}
