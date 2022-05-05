<?php

namespace EasyPanel\Http\Middleware;

use EasyPanel\Support\Contract\AuthFacade;
use Closure;

class isAdmin
{

    public function handle($request, Closure $next)
    {
        $defaultGuard = config('easy_panel.auth_guard') ?? config('auth.defaults.guard');
        $redirectPath = config('easy_panel.redirect_unauthorized') ?? '/';

        auth()->shouldUse($defaultGuard);

        if(auth()->guest()){
            return redirect($redirectPath);
        }

        if(!AuthFacade::check(auth()->user()->id)){
            return redirect($redirectPath);
        }

        return $next($request);
    }

}
