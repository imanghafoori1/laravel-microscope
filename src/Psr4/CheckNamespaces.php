<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

class CheckNamespaces
{
    private static function getCorrectNamespaces($autoloads, $relativePath)
    {
        $correctNamespaces = [];
        foreach ($autoloads as $autoload) {
            foreach ($autoload as $namespacePrefix => $path) {
                if (substr(str_replace('\\', '/', $relativePath), 0, strlen($path)) === $path) {
                    $correctNamespaces[] = NamespaceCalculator::calculateCorrectNamespace($relativePath, $path, $namespacePrefix);
                }
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

    public static function findPsr4Errors($basepath, $autoloads, $classes)
    {
        $errors = [];
        foreach ($classes as $class) {
            $error = self::checkNamespace($basepath, $autoloads, $class['currentNamespace'], $class['absFilePath'], $class['class']);

            if ($error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    public static function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && substr($haystack, 0, strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }
}
