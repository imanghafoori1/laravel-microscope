<?php

namespace EasyPanelTest\Feature\E2E;

use EasyPanelTest\TestCase;
use Livewire\Livewire;
use EasyPanel\Http\Livewire\CRUD\Create;
use EasyPanel\Models\CRUD;

class CRUDCreateTest extends TestCase
{

    /** @test * */
    public function it_opens_the_drop_down(){
        Livewire::test(Create::class)
            ->call('setModel')
            ->assertSet('dropdown', true);
    }

    /** @test * */
    public function it_closes_the_drop_down(){
        Livewire::test(Create::class)
            ->call('closeModal')
            ->assertSet('dropdown', false);
    }

    /** @test * */
    public function it_shows_the_drop_down_after_updating_model(){
        Livewire::test(Create::class)
            ->set('model', 'A')
            ->assertSet('dropdown', true);
    }

    /** @test * */
    public function it_required_model_for_crud(){
        Livewire::test(Create::class)
            ->call('create')
            ->assertHasErrors('model');
    }

    /** @test * */
   public function it_requires_route_for_crud(){
        Livewire::test(Create::class)
            ->call('create')
            ->assertHasErrors('route');
    }

    /** @test * */
    public function icon_can_be_nullable(){
        Livewire::test(Create::class)
            ->call('create')
            ->assertHasNoErrors('icon');
    }

    /** @test * */
    public function if_icon_is_not_null_it_must_be_at_least_5_char(){
        Livewire::test(Create::class)
            ->set('icon', 'fa')
            ->call('create')
            ->assertHasErrors('icon');

        Livewire::test(Create::class)
            ->set('icon', 'fa fa-user')
            ->call('create')
            ->assertHasNoErrors('icon');
    }

    /** @test * */
    public function route_must_be_at_least_2_char(){
        Livewire::test(Create::class)
            ->set('route', 'a')
            ->call('create')
            ->assertHasErrors(['route' => 'min']);

        Livewire::test(Create::class)
            ->set('route', 'user')
            ->call('create')
            ->assertHasNoErrors(['route' => 'min']);
    }

    /** @test * */
    public function model_must_be_at_least_8_char(){
        Livewire::test(Create::class)
            ->set('model', 'a')
            ->call('create')
            ->assertHasErrors(['model' => 'min']);

        Livewire::test(Create::class)
            ->set('model', 'App\\User')
            ->call('create')
            ->assertHasNoErrors(['model' => 'min']);
    }

    /** @test * */
    public function model_should_be_unique(){
        Livewire::test(Create::class)
            ->set('model', 'App\\User')
            ->call('create')
            ->assertHasNoErrors(['model' => 'unique']);

        CRUD::query()->create([
            'model' => "App\\User",
            'name' => 'user',
            'route' => 'user',
            'icon' => 'fa fa-user'
        ]);

        Livewire::test(Create::class)
            ->set('model', 'App\\User')
            ->call('create')
            ->assertHasErrors(['model' => 'unique']);
    }

    /** @test * */
    public function route_must_be_unique(){
        Livewire::test(Create::class)
            ->set('route', 'user')
            ->call('create')
            ->assertHasNoErrors(['route' => 'unique']);

        CRUD::query()->create([
            'model' => "App\\User",
            'name' => 'user',
            'route' => 'user',
            'icon' => 'fa fa-user'
        ]);

        Livewire::test(Create::class)
            ->set('route', 'user')
            ->call('create')
            ->assertHasErrors(['route' => 'unique']);
    }

}
