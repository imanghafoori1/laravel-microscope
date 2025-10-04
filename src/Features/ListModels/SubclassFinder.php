<?php

namespace Imanghafoori\LaravelMicroscope\Features\ListModels;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use ReflectionClass;
use Throwable;

class SubclassFinder
{
    public function getList($folder, $parentClass)
    {
        $filter = function ($classFilePath, $currentNamespace, $class) use ($parentClass) {
            try {
                $reflection = new ReflectionClass($currentNamespace.'\\'.$class);
            } catch (Throwable $e) {
                return false;
            }

            return $reflection->isSubclassOf($parentClass);
        };

        $pathFilter = $folder ? $this->getPathFilter($folder) : null;

        return ComposerJson::make()->getClasslists($filter, $pathFilter);
    }

    protected function getPathFilter($folder)
    {
        return fn ($absFilePath, $fileName) => strpos(str_replace(BasePath::$path, '', $absFilePath), $folder);
    }
}
