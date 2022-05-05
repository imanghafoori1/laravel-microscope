<?php

namespace EasyPanel\Commands\Actions;

use EasyPanel\EasyPanelServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Iya30n\DynamicAcl\Providers\DynamicAclServiceProvider;

class Install extends Command
{

    protected $signature = 'panel:install';
    protected $description = 'Install panel';

    public function handle()
    {
        $this->warn("\nInstalling Admin panel ...");

        Artisan::call('vendor:publish', [
            '--provider' => EasyPanelServiceProvider::class,
            '--tag' => 'easy-panel-styles'
        ]);

        Artisan::call('vendor:publish', [
            '--provider' => EasyPanelServiceProvider::class,
            '--tag' => 'easy-panel-views'
        ]);

        Artisan::call('vendor:publish', [
            '--provider' => EasyPanelServiceProvider::class,
            '--tag' => 'easy-panel-config'
        ]);

        Artisan::call('vendor:publish', [
            '--provider' => EasyPanelServiceProvider::class,
            '--tag' => 'easy-panel-cruds'
        ]);

        Artisan::call('vendor:publish', [
            '--provider' => EasyPanelServiceProvider::class,
            '--tag' => 'easy-panel-lang'
        ]);

        Artisan::call('vendor:publish', [
            '--provider' => EasyPanelServiceProvider::class,
            '--tag' => 'easy-panel-migration'
        ]);

        Artisan::call('vendor:publish', [
            '--provider' => DynamicAclServiceProvider::class
        ]);

        Artisan::call('migrate');

        $this->line("<options=bold,reverse;fg=green>\nEasy panel was installed 🎉</>\n\nBuild an amazing admin panel less than 5 minutes 🤓\n");
    }
}
