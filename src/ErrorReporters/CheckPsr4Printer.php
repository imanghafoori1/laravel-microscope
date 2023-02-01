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

    public static function reportResult($autoload)
    {
        $messages = [];
        $messages[] = '';
        $messages[] = '<fg=blue>Finished!</>';
        $separator = function ($color) {
            return ' <fg='.$color.'>'.str_repeat('_', (new Terminal)->getWidth() - 2).'</>';
        };

        try {
            $messages[] = $separator('gray');
        } catch (\Exception $e) {
            $messages[] = $separator('blue');
        }
        $messages[] = '<options=bold;fg=yellow>'.CheckNamespaces::$checkedNamespaces.' classes were checked under:</>';
        $len = 0;
        foreach ($autoload as $composerPath => $psr4) {
            $output = '';
            $messages[] = ' <fg=blue>./'.trim($composerPath.'/', '/').'composer.json'.'</>';
            foreach ($psr4 as $namespace => $path) {
                $max = max($len, strlen($namespace));
                $len = strlen($namespace);
                $output .= '   - <fg=red>'.$namespace.str_repeat(' ', $max - strlen($namespace)).' </> (<fg=green>./'.$path."</>)\n";
            }
            $messages[] = $output;
        }

        return $messages;
    }

    public static function noErrorFound($time)
    {
        $time = round(microtime(true) - $time, 5);
        $output = [];
        $output[] = [PHP_EOL.'<fg=green>All namespaces are correct!</><fg=blue> You rock  \(^_^)/ </>', 'line'];
        $output[] = ['<fg=red;options=bold>'.$time.'(s)</>', 'line'];
        $output[] = ['', 'line'];

        return $output;
    }

    public static function getErrorsCount($errorPrinter, $time)
    {
        if ($errorCount = $errorPrinter->errorsList['total']) {
            return [[PHP_EOL.$errorCount.' error(s) found.', 'warn']];
        } else {
            return CheckPsr4Printer::noErrorFound($time);
        }
    }
}
