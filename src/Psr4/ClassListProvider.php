<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

use ImanGhafoori\ComposerJson\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\TokenAnalyzer\GetClassProperties;

class ClassListProvider
{
    public static $buffer = 800;

    public function getClasslists(array $autoloads, ?\Closure $filter, ?\Closure $pathFilter)
    {
        $classLists = [];
        foreach (ComposerJson::purgeAutoloadShortcuts($autoloads) as $composerFilePath => $autoload) {
            foreach ($autoload as $namespace => $psr4Path) {
                $classes = $this->getClassesWithin($psr4Path, $filter, $pathFilter);
                $classLists[$composerFilePath][$namespace] = $classes;
            }
        }

        return $classLists;
    }

    public function getClassesWithin($composerPath, \Closure $filterClass, ?\Closure $pathFilter = null)
    {
        $results = [];
        foreach (FilePath::getAllPhpFiles($composerPath) as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();

            if ($pathFilter && ! $pathFilter($absFilePath, $classFilePath->getFilename())) {
                continue;
            }

            // Exclude blade files
            if (substr_count($classFilePath->getFilename(), '.') !== 1) {
                continue;
            }

            [$currentNamespace, $class, $parent, $type] = $this->readClass($absFilePath);

            // Skip if there is no class/trait/interface definition found.
            // For example a route file or a config file.
            if (! $class) {
                continue;
            }

            if ($filterClass($classFilePath, $currentNamespace, $class, $parent) === false) {
                continue;
            }

            $results[] = [
                'relativePath' => $classFilePath->getRelativePath(),
                'relativePathname' => $classFilePath->getRelativePathname(),
                'fileName' => $classFilePath->getFilename(),
                'currentNamespace' => $currentNamespace,
                'absFilePath' => $absFilePath,
                'class' => $class,
                'type' => $type,
            ];
        }

        return $results;
    }

    private function readClass($absFilePath)
    {
        $buffer = self::$buffer;
        do {
            [
                $currentNamespace,
                $class,
                $type,
                $parent,
            ] = GetClassProperties::fromFilePath($absFilePath, $buffer);
            $buffer = $buffer + 1000;
        } while ($currentNamespace && ! $class && $buffer < 6000);

        return [$currentNamespace, $class, $parent, $type];
    }
}
