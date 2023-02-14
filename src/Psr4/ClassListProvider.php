<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

use ImanGhafoori\ComposerJson\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\TokenAnalyzer\GetClassProperties;

class ClassListProvider
{
    public static $checkedNamespacesStats = [];

    public static $buffer = 800;

    public function getClasslists(array $autoloads, $folder, ?\Closure $filter)
    {
        $classLists = [];
        foreach (ComposerJson::purgeAutoloadShortcuts($autoloads) as $path => $autoload) {
            $classLists[$path] = [];
            foreach ($autoload as $namespace => $psr4Path) {
                $classes = $this->getClassesWithin($psr4Path, $folder, $filter);
                self::$checkedNamespacesStats[$namespace] = count($classes);
                $classLists[$path] = array_merge(
                    $classLists[$path],
                    $classes
                );
            }
        }

        return $classLists;
    }

    public function getClassesWithin($composerPath, $folder, \Closure $filterClass, ?\Closure $filterPath = null)
    {
        $results = [];
        foreach (FilePath::getAllPhpFiles($composerPath) as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();

            if ($filterPath && ! $filterPath($absFilePath, $classFilePath->getFilename())) {
                continue;
            }

            if ($folder && ! strpos($absFilePath, $folder)) {
                continue;
            }

            // Exclude blade files
            if (substr_count($classFilePath->getFilename(), '.') === 2) {
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
