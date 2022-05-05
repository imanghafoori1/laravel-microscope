<?php

namespace EasyPanel\Support\Auth;

use EasyPanel\Support\Contract\UserProviderFacade;

class AdminIdentifier
{

    public function check($userId)
    {
        $user = UserProviderFacade::findUser($userId);

        return $user->panelAdmin()->exists();
    }

}
