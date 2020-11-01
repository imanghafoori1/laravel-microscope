<?php

namespace Imanghafoori\LaravelMicroscope\Stubs;

class ServiceProviderStub
{
    public static function providerContent($correctNamespace, $className)
    {
        $viewNamespace = str_replace('ServiceProvider', '', $className);
        $string = file_get_contents(__DIR__.'/microscopeServiceProvider.stub');
        $mapping = [
            '$correctNamespace' => $correctNamespace,
            '$className' => $className,
            '$viewNamespace' => $viewNamespace,
        ];

        return '<?php'.str_replace(array_keys($mapping), array_values($mapping), $string);
    }
}
