<?php

namespace EasyPanelTest\Integration;


use EasyPanel\Models\PanelAdmin;
use EasyPanelTest\Dependencies\User;

class PanelAdminModelTest extends \EasyPanelTest\TestCase
{

    /** @test * */
    public function user_relation_is_an_instance_of_user_model(){
        config()->set('easy_panel.user_model', User::class);

        $panelAdmin = PanelAdmin::query()->create([
            'user_id' => $this->user->id,
            'is_superuser' => false
        ]);

        $this->assertInstanceOf(User::class, $panelAdmin->user);
    }

}
