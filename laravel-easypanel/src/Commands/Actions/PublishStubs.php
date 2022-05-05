<?php

namespace EasyPanel\Commands\Actions;

use EasyPanel\EasyPanelServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PublishStubs extends Command
{

    protected $signature = 'panel:publish';
    protected $description = 'Publish stubs of package';

    public function handle()
    {
        Artisan::call('vendor:publish', [
            '--provider' => EasyPanelServiceProvider::class,
            '--tag' => 'easy-panel-stubs'
        ]);

        $this->info("Stubs was published successfully");
    }
}
