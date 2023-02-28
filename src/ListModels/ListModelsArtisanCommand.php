<?php

namespace Imanghafoori\LaravelMicroscope\ListModels;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use ReflectionClass;
use Symfony\Component\Console\Terminal;
use Throwable;

class ListModelsArtisanCommand extends Command
{
    protected $signature = 'list:models {--folder=}';

    protected $description = 'Lists Eloquent Models';

    public function handle()
    {
        $models = $this->getModelsLists();

        $data = $this->inspectModels($models);

        $this->printList($data);
    }

    protected function inspectModels($classLists)
    {
        $models = [];
        foreach ($classLists as $path => $classList) {
            $models[$path] = [];
            foreach ($classList as $list) {
                foreach ($list as $class) {
                    $classPath = $class['currentNamespace'].'\\'.$class['class'];
                    $table = (new ReflectionClass($classPath))->newInstanceWithoutConstructor()->getTable();
                    $models[$path][] = [
                        'table' => $table,
                        'class' => $classPath,
                        'relative_path' => str_replace(base_path(), '', $class['absFilePath']),
                    ];
                }
            }
        }

        return $models;
    }

    protected function getPathFilter(string $folder)
    {
        return function ($absFilePath, $fileName) use ($folder) {
            return strpos(str_replace(base_path(), '', $absFilePath), $folder);
        };
    }

    protected function getModelsLists()
    {
        $folder = ltrim($this->option('folder'), '=');
        $filter = function ($classFilePath, $currentNamespace, $class, $parent) {
            try {
                $reflection = new ReflectionClass($currentNamespace.'\\'.$class);
            } catch (Throwable $e) {
                return false;
            }

            return $reflection->isSubclassOf(Model::class);
        };

        $pathFilter = $folder ? $this->getPathFilter($folder) : null;

        return ComposerJson::make()->getClasslists($filter, $pathFilter);
    }

    protected function printList($models)
    {
        $output = $this->getOutput();
        foreach ($models as $path => $modelList) {
            $output->writeln(' - '.$path);
            foreach ($modelList as $model) {
                $output->writeln('    <fg=yellow>'.$model['class'].'</>   (<fg=blue>\''.$model['table'].'\'</>)');
                $output->writeln(str_replace('\\', '/', ErrorPrinter::getLink($model['relative_path'])));

                try {
                    $msg = '<fg=gray>';
                } catch (Exception $e) {
                    // for older version of laravel.
                    $msg = '<fg=white>';
                }

                $output->writeln($msg.str_repeat('_', (new Terminal())->getWidth()).'</>');
            }
        }
    }
}
