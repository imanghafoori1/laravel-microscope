<?php

namespace EasyPanelTest\Feature;

use EasyPanelTest\TestCase;
use Illuminate\Support\Facades\File;

class StubPublisherCommandTest extends TestCase
{
    /** @test * */
    public function it_publishes_stubs(){
        $this->artisan('panel:publish')
            ->expectsOutput('Stubs was published successfully');

        $this->assertDirectoryExists(base_path('/stubs/panel'));
    }
}
