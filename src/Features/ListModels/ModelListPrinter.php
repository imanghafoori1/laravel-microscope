<?php

namespace Imanghafoori\LaravelMicroscope\Features\ListModels;

use Exception;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class ModelListPrinter
{
    public function printList($models, $output, $terminalWidth)
    {
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

                $output->writeln($msg.str_repeat('_', $terminalWidth).'</>');
            }
        }
    }
}
