<?php

namespace EasyPanelTest;

use EasyPanel\EasyPanelServiceProvider;
use EasyPanel\Parsers\StubParser;
use EasyPanelTest\Dependencies\User;
use Faker\Factory;
use Illuminate\Support\Facades\Hash;
use Iya30n\DynamicAcl\Providers\DynamicAclServiceProvider;
use Javoscript\MacroableModels\MacroableModelsServiceProvider;
use Livewire\LivewireServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{

    /**
     * @var \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    protected $user;

    /**
     * @var StubParser
     */
    protected $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/Dependencies/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../vendor/iya30n/dynamic-acl/database/migrations');
        $this->setUser();
        $this->setParser();
        
        config()->set('easy_panel.user_model', User::class);
    }

    protected function getPackageProviders($app)
    {
        return [
            EasyPanelServiceProvider::class,
            LivewireServiceProvider::class,
            MacroableModelsServiceProvider::class,
            DynamicAclServiceProvider::class,
        ];
    }

    protected function setUser()
    {
        $faker = Factory::create();
        $user = User::create(['name' => $faker->name, 'password' => Hash::make('password')]);
        $this->user = $user;
    }

    public function getAdmin()
    {
        $this->user->panelAdmin()->create([
            'is_superuser' => true
        ]);

        return $this->user->refresh();
    }

    private function setParser()
    {
        $this->parser = new StubParser('article', \App\Models\Article::class);
    }
}
