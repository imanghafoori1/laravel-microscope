<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Psr4\CheckNamespaces;
use Symfony\Component\Console\Terminal;


class CheckPsr4Printer extends ErrorPrinter
{
    public static function warnIncorrectNamespace($relativePath, $currentNamespace, $correctNamespace, $class, $command)
    {
        /**
         * @var $p ErrorPrinter
         */
        $p = app(ErrorPrinter::class);
        $msg = 'Incorrect namespace: '.$p->color("namespace $currentNamespace;");
        PendingError::$maxLength = max(PendingError::$maxLength, strlen($msg));
        $p->end();
        $currentNamespace && $p->printHeader('Incorrect namespace: '.$p->color("namespace $currentNamespace;"));
        ! $currentNamespace && $p->printHeader('Namespace Not Found: '.$class);
        $p->printLink($relativePath, 3);

        return CheckPsr4Printer::ask($command, $correctNamespace);
    }

    public static function ask($command, $correctNamespace)
    {
        if ($command->option('nofix')) {
            return false;
        }

        if ($command->option('force')) {
            return true;
        }

        return $command->getOutput()->confirm('Do you want to change it to: <fg=blue>'.$correctNamespace.'</>', true);
    }

    public static function reportResult($command)
    {
        $command->getOutput()->writeln('');
        $command->getOutput()->writeln('<fg=blue>Finished!</>');
        $separator = function ($color) use ($command) {
            $command->info(' <fg='.$color.'>'.str_repeat('_', (new Terminal)->getWidth() - 2).'</>');
        };

        try {
            $separator('gray');
        } catch (\Exception $e) {
            $separator('blue');
        }
        $command->getOutput()->writeln('<options=bold;fg=yellow>'.CheckNamespaces::$checkedNamespaces.' classes were checked under:</>');
        $len = 0;
        foreach (ComposerJson::readAutoload() as $composerPath => $psr4) {
            $output = '';
            $command->getOutput()->writeln(' <fg=blue>./'.trim($composerPath.'/', '/').'composer.json'.'</>');
            foreach ($psr4 as $namespace => $path) {
                $max = max($len, strlen($namespace));
                $len = strlen($namespace);
                $output .= '   - <fg=red>'.$namespace.str_repeat(' ', $max - strlen($namespace)).' </> (<fg=green>./'.$path."</>)\n";
            }
            $command->getOutput()->writeln($output);
        }
    }

    public static function noErrorFound($time, $command)
    {
        $time = microtime(true) - $time;
        $command->line(PHP_EOL.'<fg=green>All namespaces are correct!</><fg=blue> You rock  \(^_^)/ </>');
        $command->line('<fg=red;options=bold>'.round($time, 5).'(s)</>');
        $command->line('');
    }

    public static function printErrorsCount($errorPrinter, $time, $command)
    {
        if ($errorCount = $errorPrinter->errorsList['total']) {
            $command->warn(PHP_EOL.$errorCount.' error(s) found.');
        } else {
            CheckPsr4Printer::noErrorFound($time, $command);
        }
    }

}
