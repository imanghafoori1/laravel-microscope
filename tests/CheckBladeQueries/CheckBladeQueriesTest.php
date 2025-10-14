<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckBladeQueries;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckBladeQueries\CheckBladeQueriesCommand;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CheckBladeQueriesTest extends TestCase
{
    #[Test]
    public function check_dd_command()
    {
        $iterator = new class
        {
            public $count = 0;

            public $printAllInput;

            public function forBladeFiles()
            {
                $this->count++;

                return '123';
            }

            public function printAll($input)
            {
                $this->printAllInput = $input;
            }
        };

        $command = new CheckBladeQueriesCommand();
        $command->handleCommand($iterator);
        $this->assertEquals(['123'], $iterator->printAllInput);
        $this->assertEquals(1, $iterator->count);

        $check = $command->checks[0];
        $file = PhpFileDescriptor::make(__DIR__.DIRECTORY_SEPARATOR.'query_in_blade.stub');
        $check::check($file);

        $errors = ErrorPrinter::singleton()->errorsList['queryInBlade'];
        $this->assertCount(1, $errors);
    }
}
