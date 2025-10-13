<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckEvent;

use Imanghafoori\LaravelMicroscope\Features\CheckEvents\CheckEventsCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CheckEventCommandTest extends TestCase
{
    public function setUp(): void
    {
        $_SESSION['msg'] = [];
    }

    public function tearDown(): void
    {
        unset($_SESSION['msg']);
    }

    #[Test]
    public function basic()
    {
        // arrange:
        $command = new CheckEventsCommand();
        $command->output(new class
        {
            public function writeln($msg)
            {
                $_SESSION['msg'][] = $msg;
            }
        });

        // act:
        $command->handleCommand();

        // assert:
        $this->assertEquals($_SESSION['msg'], [' - 0 listenings were checked.']);
    }
}
