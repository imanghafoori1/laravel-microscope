<?php

namespace Imanghafoori\LaravelMicroscope\Tests\RoutelessControllerActionsCheck;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Features\CheckDeadControllers\RoutelessControllerActions;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RoutelessControllerActionsTest extends TestCase
{
    public function setUp(): void
    {
        copy(__DIR__.'/SampleController.stub', __DIR__.'/SampleController.php');
    }

    public function tearDown(): void
    {
        unlink(__DIR__.'/SampleController.php');
    }

    #[Test]
    public function check()
    {
        ComposerJson::$composer = function () {
            return new class
            {
                public function getNamespacedClassFromPath()
                {
                    return 'Imanghafoori\\LaravelMicroscope\\Tests\\RoutelessControllerActionsCheck\\SampleController';
                }
            };
        };
        $file = PhpFileDescriptor::make(__DIR__.'/SampleController.php');
        RoutelessControllerActions::$baseController = RuntimeException::class;
        RoutelessControllerActions::$routes = new class
        {
            public static function hasRoute()
            {
                return false;
            }
        };
        RoutelessControllerActions::check($file);

        $this->assertTrue(true);
    }
}