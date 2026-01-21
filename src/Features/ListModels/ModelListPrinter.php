<?php

namespace Imanghafoori\LaravelMicroscope\Features\ListModels;

use Exception;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;

class ModelListPrinter
{
    public function printList($models, $output, $terminalWidth)
    {
        foreach ($models as $path => $modelList) {
            $output->writeln(' - '.$path);
            foreach ($modelList as $model) {
                $table = Color::blue($model['table']);
                $class = Color::yellow($model['class']);
                $output->writeln("    $class   ('$table')");
                $output->writeln(str_replace('\\', '/', ErrorPrinter::getLink($model['relative_path'])));

                $line = str_repeat('_', $terminalWidth);
                try {
                    $output->writeln(Color::gray($line));
                } catch (Exception $e) {
                    // for older version of laravel.
                    $output->writeln(Color::white($line));
                }
            }
        }
    }
}
