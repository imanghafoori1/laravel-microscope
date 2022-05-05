<?php

namespace EasyPanel\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LangChanger
{

    public function handle($request, Closure $next)
    {
        $lang = session()->has('easypanel_lang')
            ? session()->get('easypanel_lang')
            : (config('easy_panel.lang') ?? 'en') .'_panel';

        App::setLocale($lang);

        return $next($request);
    }

}
