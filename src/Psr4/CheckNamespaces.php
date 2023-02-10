<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

class CheckNamespaces
{
    public static function getErrorsLists($basePath, array $autoloads, array $classLists, ?\Closure $onCheck)
    {
        $errorsLists = [];
        foreach ($classLists as $path => $classList) {
            $errorsLists[$path] = self::findPsr4Errors($basePath, $autoloads[$path], $classList, $onCheck);
        }

        return $errorsLists;
    }

    public static function checkNamespace($basepath, $autoloads, $currentNamespace, $absFilePath, $class)
    {
        $relativePath = \trim(str_replace($basepath, '', $absFilePath), '/\\');
        $correctNamespaces = self::getCorrectNamespaces($autoloads, $relativePath);

        if (! in_array($currentNamespace, $correctNamespaces)) {
            $correctNamespace = self::findShortest($correctNamespaces);

            return [
                'absPath' => $absFilePath,
                'from' => $currentNamespace,
                'to' => $correctNamespace,
                'class' => $class,
                'type' => 'namespace',
            ];
        } elseif (($class.'.php') !== basename($absFilePath)) {
            return [
                'relativePath' => $relativePath,
                'fileName' => basename($absFilePath),
                'class' => $class,
                'type' => 'filename',
            ];
        }
    }

    public static function findPsr4Errors($basePath, $autoloads, $classes, ?\Closure $onCheck)
    {
        $errors = [];
        foreach ($classes as $class) {
            $onCheck && $onCheck($class);
            $error = self::checkNamespace($basePath, $autoloads, $class['currentNamespace'], $class['absFilePath'], $class['class']);

            if ($error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    public static function getCorrectNamespaces($autoloads, $relativePath)
    {
        $correctNamespaces = [];
        foreach ($autoloads as $namespacePrefix => $path) {
            if (substr(str_replace('\\', '/', $relativePath), 0, strlen($path)) === $path) {
                $correctNamespaces[] = NamespaceCalculator::calculateCorrectNamespace($relativePath, $path, $namespacePrefix);
            }
        }

        return $correctNamespaces;
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
