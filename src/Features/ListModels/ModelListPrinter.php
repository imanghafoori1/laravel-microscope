<?php

namespace Imanghafoori\LaravelMicroscope\Features\ListModels;

use Exception;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;

class ModelListPrinter
{
    public function printList($models, $output)
    {
        foreach ($models as $path => $modelList) {
            $output->writeln(" - $path");
            Loop::over(
                $modelList,
                fn ($model) => $this->printModel($model, $output)
            );
        }
    }

    private function printModel($model, $output): void
    {
        $table = Color::blue($model['table']);
        $class = Color::yellow($model['class']);
        $output->writeln("    $class   ('$table')");
        $output->writeln(str_replace('\\', '/', ErrorPrinter::getLink($model['relative_path'])));

        $line = str_repeat('_', ErrorPrinter::$terminalWidth - 3);
        try {
            $output->writeln(Color::gray($line));
        } catch (Exception $e) {
            // for older version of laravel.
            $output->writeln(Color::white($line));
        }
    }
}
