<?php

namespace EasyPanel\Commands\Actions;

use Illuminate\Console\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use EasyPanel\Models\CRUD;

class MakeCRUD extends Command
{

    protected $signature = 'panel:crud {name?} {--f|force : Force mode}';
    protected $description = 'Create all action for CRUDs';

    public function handle()
    {
        $names = $this->argument('name') ?
            [$this->argument('name')] :
            CRUD::query()->where('active', true)->pluck('name')->toArray();

        if(is_null($names)) {
            throw new CommandNotFoundException("There is no action in config file");
        }

        foreach ($names as $name) {
            $args = ['name' => $name, '--force' => $this->option('force')];
            $instance = getCrudConfig($name);

            $this->createActions($instance, $name, $args);
        }
    }

    private function createActions($instance, $name, $args)
    {
        if (isset($instance->create) and $instance->create) {
            $this->call('panel:create', $args);
        } else {
            $this->warn("The create action is disabled for {$name}");
        }

        if (isset($instance->update) and $instance->update) {
            $this->call('panel:update', $args);
        } else {
            $this->warn("The update action is disabled for {$name}");
        }

        $this->call('panel:read', $args);
        $this->call('panel:single', $args);
    }

}
