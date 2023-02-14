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

    public static function checkNamespace($relativePath, $psr4Mapping, $currentNamespace, $class, $fileName)
    {
        $correctNamespaces = NamespaceCalculator::getCorrectNamespaces($psr4Mapping, $relativePath);

        if (! in_array($currentNamespace, $correctNamespaces)) {
            return [
                'type' => 'namespace',
                'correctNamespace' => self::findShortest($correctNamespaces),
            ];
        } elseif (($class.'.php') !== $fileName) {
            return [
                'type' => 'filename',
            ];
        }
    }

    public static function findPsr4Errors($basePath, $psr4Mapping, $classes, $composerPath, ?\Closure $onCheck)
    {
        $errors = [];
        foreach ($classes as $class) {
            $onCheck && $onCheck($class);
            $relativePath = \trim(str_replace($basePath, '', $class['absFilePath']), '/\\');
            $error = self::checkNamespace($relativePath, $psr4Mapping, $class['currentNamespace'], $class['class'], $class['fileName']);

            if ($error) {
                $error['relativePath'] = $relativePath;
                $error = $error + $class;
                $errors[] = $error;
            }
        }

        return $errors;
    }

    private static function findShortest($correctNamespaces)
    {
        // finds the shortest namespace
        return array_reduce($correctNamespaces, function ($a, $b) {
            if ($a === null) {
                return $b;
            }

            return strlen($a) < strlen($b) ? $a : $b;
        });
    }
}
