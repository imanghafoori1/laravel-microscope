<?php

namespace Imanghafoori\LaravelMicroscope\Stubs;

class ServiceProviderStub
{
    public static function providerContent($correctNamespace, $className)
    {
        return '<?php
                
namespace '.$correctNamespace.';

use Illuminate\Support\ServiceProvider;

class '.$className.' extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.\'/routes.php\');
    }
}
';
    }
}
