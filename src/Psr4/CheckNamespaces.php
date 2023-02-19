<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

use ImanGhafoori\ComposerJson\NamespaceCalculator;

class CheckNamespaces
{
    public static function getErrorsLists(string $basePath, array $autoloads, array $classLists, ?\Closure $onCheck)
    {
        $errorsLists = [];
        foreach ($classLists as $composerPath => $classList) {
            $errorsLists[$composerPath] = self::findPsr4Errors($basePath, $autoloads[$composerPath], $classList, $composerPath, $onCheck);
        }

        return $errorsLists;
    }

    public static function findPsr4Errors($basePath, $psr4Mapping, $classLists, $composerPath, ?\Closure $onCheck)
    {
        $errors = [];
        foreach ($classLists as $list) {
            foreach ($list as $class) {
                $onCheck && $onCheck($class);
                $relativePath = \trim(str_replace($basePath, '', $class['absFilePath']), '/\\');
                $error = NamespaceCalculator::checkNamespace($relativePath, $psr4Mapping, $class['currentNamespace'], $class['class'], $class['fileName']);

                if ($error) {
                    $error['relativePath'] = $relativePath;
                    $error = $error + $class;
                    $errors[] = $error;
                }
            }
        }

        return $errors;
    }
}
