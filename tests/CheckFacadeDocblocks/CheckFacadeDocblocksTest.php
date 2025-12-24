<?php

namespace Imanghafoori\LaravelMicroscope\Tests\CheckFacadeDocblocks;

use Imanghafoori\LaravelMicroscope\Features\CheckFacadeDocblocks\FacadeDocblocks;
use Imanghafoori\LaravelMicroscope\Foundations\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use PHPUnit\Framework\TestCase;

class CheckFacadeDocblocksTest extends TestCase
{
    public function setUp(): void
    {
        copy(__DIR__.'/SampleFacade.php', __DIR__.'/SampleFacade.temp');
    }

    public function tearDown(): void
    {
        unlink(__DIR__.'/SampleFacade.temp');
        SampleFacade::setFacadeApplication(null);
    }

    public function test_basic()
    {
        ComposerJson::$composer = function () {
            return new class
            {
                public function getNamespacedClassFromPath()
                {
                    return 'Imanghafoori\LaravelMicroscope\Tests\CheckFacadeDocblocks\SampleFacade';
                }
            };
        };
        $file = PhpFileDescriptor::make(__DIR__.'/SampleFacade.temp');
        SampleFacade::swap(new MySampleRoot());
        SampleFacade::setFacadeApplication(new class
        {
            public function bound()
            {
                return true;
            }
        });
        $_SESSION['facade_fix'] = 0;
        FacadeDocblocks::$onFix = function () {
            $_SESSION['facade_fix']++;
        };
        FacadeDocblocks::check($file);

        $this->assertEquals(
            file_get_contents(__DIR__.'/SampleFacade-result.stub'),
            trim(file_get_contents(__DIR__.'/SampleFacade.temp'))
        );
        $this->assertEquals(1, $_SESSION['facade_fix']);
    }
}
