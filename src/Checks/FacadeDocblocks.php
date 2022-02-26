<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Support\Facades\Facade;
use Imanghafoori\LaravelMicroscope\Psr4\NamespaceCorrector;
use Imanghafoori\RealtimeFacades\SmartRealTimeFacadesProvider;
use Symfony\Component\Finder\SplFileInfo;

class FacadeDocblocks
{
    public static $command;

    public static function check($tokens, $absFilePath, SplFileInfo $classFilePath, $psr4Path, $psr4Namespace)
    {
        $class = NamespaceCorrector::getNamespacedClassFromPath($absFilePath);

        if (class_exists($class) && is_subclass_of($class, Facade::class)) {
            $cb = (function ($class) {
                return $class::getFacadeAccessor();
            })->bindTo(null, $class);

            $accessor = $cb($class);

            if (is_object($accessor)) {
                $accessor = get_class($accessor);
            }

            if (! is_string($accessor)) {
                return;
            }

            if (class_exists($accessor) || interface_exists($accessor)) {
                SmartRealTimeFacadesProvider::getMethodsDocblock($accessor);
            } else {
                $accessor = get_class($class::getFacadeRoot());
                SmartRealTimeFacadesProvider::getMethodsDocblock($accessor);
            }
        }
    }
}
