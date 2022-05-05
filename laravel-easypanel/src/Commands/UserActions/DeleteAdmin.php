<?php

namespace EasyPanel\Commands\UserActions;

use EasyPanel\Support\Contract\UserProviderFacade;
use Illuminate\Console\Command;

class DeleteAdmin extends Command
{

    protected $signature = 'panel:remove {user} {--f|force}';
    protected $description = 'Remove an admin with user id';

    public function handle()
    {
        $user = $this->argument('user');

        if($this->askResult($user)){
            UserProviderFacade::deleteAdmin($user);
            $this->info('Admin was removed successfully');
            return;
        }

        $this->warn('Process was canceled');
    }

    public function askResult($user)
    {
        if($this->option('force')) {
            return true;
        }

        return $this->confirm("Do you want to remove {$user} from administration", 'yes');
    }
}
