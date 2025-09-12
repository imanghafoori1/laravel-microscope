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
                    $this->write($output, '<fg=gray>', $terminalWidth);
                } catch (Exception $e) {
                    // for older version of laravel.
                    $this->write($output, '<fg=white>', $terminalWidth);
                }
            }
        }
    }

    private function write($output, string $msg, $terminalWidth): void
    {
        $output->writeln($msg.str_repeat('_', $terminalWidth).'</>');
    }
}
