<?php

namespace Imanghafoori\LaravelMicroscope\Features\ServiceProviderGenerator;

class ServiceProviderStub
{
    public static function providerContent($correctNamespace, $className, $prefix)
    {
        $template = file_get_contents(__DIR__.'/microscopeServiceProvider.stub');

        $mapping = [
            '$correctNamespace' => $correctNamespace,
            '$className' => $className,
            '$name' => $prefix,
        ];

        return '<?php'.str_replace(array_keys($mapping), array_values($mapping), $template);
    }
}
