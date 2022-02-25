<?php

namespace Imanghafoori\LaravelMicroscope\Traits;

use Imanghafoori\LaravelMicroscope\Psr4\NamespaceCorrector;

trait CorrectNamespace
{
    public static function getFullNamespace($classFilePath, $psr4Path, $psr4Namespace)
    {
        $fullNamespace = self::getNamespacedClassName($classFilePath, $psr4Path, $psr4Namespace);

        return \trim($fullNamespace, '.php');
    }

    public static function getNamespacedClassName($classFilePath, $psr4Path, $psr4Namespace)
    {
        $absFilePath = $classFilePath->getRealPath();
        $className = $classFilePath->getFilename();
        $relativePath = \str_replace(base_path(), '', $absFilePath);
        $namespace = NamespaceCorrector::calculateCorrectNamespace($relativePath, $psr4Path, $psr4Namespace);

        return $namespace.'\\'.$className;
    }
}
