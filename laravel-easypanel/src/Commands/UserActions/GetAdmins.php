<?php

namespace EasyPanel\Commands\UserActions;

use EasyPanel\Support\Contract\UserProviderFacade;
use Illuminate\Console\Command;

class GetAdmins extends Command
{

    protected $description = 'Get Admins list';
    protected $signature = 'panel:admins';

    public function handle()
    {
        $admins = UserProviderFacade::getAdmins();
        $this->warn('Admin Lists :');
        foreach ($admins as $admin){
            $message = $admin->panelAdmin->is_superuser
                ? "• {$admin->name}: {$admin->email} ( Super Admin ✅ )"
                : "• {$admin->name}: {$admin->email}";

            $this->warn($message);
        }
    }
}
