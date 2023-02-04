<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

class CheckNamespaces
{
    /**
     * Checks all the psr-4 loaded classes to have correct namespace.
     *
     * @param  $autoloads
     * @return array
     */
    public static function findAllClass($autoloads)
    {
        $scanned = [];
        $map = [];
        foreach ($autoloads as $autoload) {
            foreach ($autoload as $namespace => $psr4Path) {
                // to avoid duplicate scanning
                foreach ($scanned as $s) {
                    if (strlen($psr4Path) > strlen($s) && self::startsWith($psr4Path, $s)) {
                        continue 2;
                    }
                }

                $scanned[] = $psr4Path;

                $map[$namespace] = $psr4Path;
            }
        }

        return $map;
    }

    public static function changeNamespace($absPath, $from, $to, $class)
    {
        NamespaceFixer::fix($absPath, $from, $to);

        return self::changedNamespaces($class, $from, $to);
    }

    private static function changedNamespaces($class, $currentNamespace, $correctNamespace)
    {
        if (! $currentNamespace) {
            return null;
        }

        $currentClass = $currentNamespace.'\\'.$class;
        $correctClass = $correctNamespace.'\\'.$class;

        return [$currentClass => $correctClass];
    }

    private static function getCorrectNamespaces($autoloads, $relativePath)
    {
        $correctNamespaces = [];
        foreach ($autoloads as $autoload) {
            foreach ($autoload as $namespacePrefix => $path) {
                if (substr(str_replace('\\', '/', $relativePath), 0, strlen($path)) === $path) {
                    $correctNamespaces[] = NamespaceCorrector::calculateCorrectNamespace($relativePath, $path, $namespacePrefix);
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
