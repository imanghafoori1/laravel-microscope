<?php

namespace EasyPanelTest\Unit;

use EasyPanelTest\TestCase;

class ConfigTest extends TestCase
{
    /** @test * */
    public function config_is_defined(){
        $this->assertNotNull(config('easy_panel'));
    }
}
