<?php

namespace EasyPanelTest\Feature;

use EasyPanel\Support\Contract\UserProviderFacade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use EasyPanelTest\TestCase;

class UserProviderFacadeTest extends TestCase
{
    use RefreshDatabase;

    /** @test * */
    public function find_a_real_user_by_id(){
        $userFoundByFacade = UserProviderFacade::findUser($this->user->id);
        $this->assertEquals($userFoundByFacade->name, $this->user->name);
    }

    /** @test * */
    public function make_an_admin_with_provider(){
        $id = $this->user->id;
        $result = UserProviderFacade::makeAdmin($id);
        $this->assertTrue((bool) $this->user->panelAdmin()->exists());
        $this->assertEquals($result['type'], 'success');
    }

    /** @test * */
    public function remove_an_admin_with_provider(){
        $id = $this->user->id;
        UserProviderFacade::makeAdmin($id);
        $this->assertTrue((bool) $this->user->panelAdmin()->exists());
        UserProviderFacade::deleteAdmin($id);
        $this->assertFalse((bool) $this->user->panelAdmin()->exists());
    }

    /** @test * */
    public function get_admins_list(){
        $id = $this->user->id;
        UserProviderFacade::makeAdmin($id);
        $adminsId = UserProviderFacade::getAdmins()->pluck('id');
        $this->assertContains($id, $adminsId);
    }

    /** @test * */
    public function user_can_be_a_super_admin(){
        $id = $this->user->id;
        UserProviderFacade::makeAdmin($id, true);
        $this->assertTrue((bool) $this->user->panelAdmin()->where('is_superuser', 1)->exists());
    }

    /** @test * */
    public function user_cannot_be_admin_twice(){
        $id = $this->user->id;
        UserProviderFacade::makeAdmin($id);
        $result = UserProviderFacade::makeAdmin($id);
        $this->assertEquals($result['type'], 'error');
    }
}
