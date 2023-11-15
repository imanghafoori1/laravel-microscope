<?php

namespace Imanghafoori\LaravelMicroscope\Features\ListModels;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Symfony\Component\Console\Terminal;

class ListModelsArtisanCommand extends Command
{
    protected $signature = 'list:models {--folder=}';

    protected $description = 'Lists Eloquent Models';

    public function handle()
    {
        $folder = ltrim($this->option('folder'), '=');

        $models = app(SubclassFinder::class)->getList($folder, Model::class);

        app(ModelListPrinter::class)->printList(
            $this->inspectModels($models),
            $this->getOutput(),
            (new Terminal())->getWidth()
        );
    }

    protected function inspectModels($classLists)
    {
        $models = [];
        foreach ($classLists as $path => $classList) {
            $models[$path] = [];
            foreach ($classList as $list) {
                foreach ($list as $class) {
                    $classPath = $class['currentNamespace'].'\\'.$class['class'];
                    $models[$path][] = [
                        'table' => $this->getTable($classPath),
                        'class' => $classPath,
                        'relative_path' => str_replace(base_path(), '', $class['absFilePath']),
                    ];
                }
            }
        }

        return $models;
    }

    private function getTable(string $classPath)
    {
        return (new ReflectionClass($classPath))->newInstanceWithoutConstructor()->getTable();
    }
}
