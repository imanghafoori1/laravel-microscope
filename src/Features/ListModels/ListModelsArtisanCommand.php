<?php

namespace Imanghafoori\LaravelMicroscope\Features\ListModels;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use ImanGhafoori\ComposerJson\ClassLists;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use ReflectionClass;

class ListModelsArtisanCommand extends Command
{
    protected $signature = 'list:models {--folder=}';

    protected $description = 'Lists Eloquent Models';

    public static $parentModel = Model::class;

    public function handle()
    {
        $folder = ltrim($this->option('folder'), '=');

        $models = app(SubclassFinder::class)->getList($folder, self::$parentModel);

        app(ModelListPrinter::class)->printList(
            $this->inspectModels($models),
            $this->getOutput(),
            ErrorPrinter::$terminalWidth
        );
    }

    protected function inspectModels(ClassLists $classLists)
    {
        $models = [];
        foreach ($classLists->getAllLists() as $path => $classList) {
            $models[$path] = [];
            foreach ($classList as $list) {
                foreach ($list as $class) {
                    $classPath = $class['currentNamespace'].'\\'.$class['class'];
                    $models[$path][] = $this->getModelInfo($classPath, $class['absFilePath']);
                }
            }
        }

        return $models;
    }

    private function getModelInfo(string $classPath, string $absFilePath)
    {
        return [
            'table' => $this->getTable($classPath),
            'class' => $classPath,
            'relative_path' => $this->getRelativePath($absFilePath),
        ];
    }

    private function getTable(string $classPath)
    {
        return (new ReflectionClass($classPath))->newInstanceWithoutConstructor()->getTable();
    }

    private function getRelativePath(string $absFilePath)
    {
        return str_replace(BasePath::$path, '', $absFilePath);
    }
}
