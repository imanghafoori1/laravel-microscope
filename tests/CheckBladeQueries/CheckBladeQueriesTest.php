<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckBladeQueries;

use Imanghafoori\LaravelMicroscope\Features\CheckDeadControllers\CheckDeadControllersCommand;
use Imanghafoori\LaravelMicroscope\Features\CheckDeadControllers\RoutelessControllerActions;
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

            public function formatPrintPsr4()
            {
                $this->count++;
            }
        };

        $command = new CheckDeadControllersCommand();
        $command->handleCommand($iterator);
        $this->assertEquals([RoutelessControllerActions::class], $command->checks);
        $this->assertEquals(1, $iterator->count);
    }
}
