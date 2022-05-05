<?php

namespace EasyPanel\Commands\Actions;

use EasyPanel\EasyPanelServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class Migration extends Command
{

    protected $signature = 'panel:migration';
    protected $description = 'Publish migrations file';

    public function handle()
    {
        Artisan::call('vendor:publish', [
            '--provider' => EasyPanelServiceProvider::class,
            '--tag' => 'easy-panel-migrations'
        ]);

        $this->line("<options=bold,reverse;fg=green>\nMigrations were published</>");
    }
}
