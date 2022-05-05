<?php

namespace EasyPanel\Commands\UserActions;

use EasyPanel\Support\Contract\UserProviderFacade;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{

    protected $description = 'Register an new admin';

    protected $signature = 'panel:add {user} {--s|super : Admin will be a super user}';

    public function handle()
    {
        $user = $this->argument('user');
        try{
            $status = UserProviderFacade::makeAdmin($user, $this->option('super'));
            $method = $status['type'] == 'success' ? 'info' : 'warn';

            $this->$method($status['message']);
        } catch (\Exception $exception){
            $this->warn("Something went wrong!\nError: ". $exception->getMessage());
        }
    }

}
