<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\PendingError;
use Symfony\Component\Console\Terminal;

class CheckPsr4Printer extends ErrorPrinter
{
    public static function warnIncorrectNamespace($relativePath, $currentNamespace, $class)
    {
        /**
         * @var $p ErrorPrinter
         */
        $p = app(ErrorPrinter::class);
        $msg = 'Incorrect namespace: '.$p->color("namespace $currentNamespace;");
        PendingError::$maxLength = max(PendingError::$maxLength, strlen($msg));
        $p->end();

        if ($currentNamespace) {
            $header = 'Incorrect namespace: '.$p->color("namespace $currentNamespace;");
        } else {
            $header = 'Namespace Not Found: '.$class;
        }

        $p->printHeader($header);
        $p->printLink($relativePath, 3);
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

    public static function reportResult($autoload, $stats, $time, $typesStats)
    {
        $messages = [];
        $separator = function ($color) {
            return ' <fg='.$color.'>'.str_repeat('_', (new Terminal)->getWidth() - 2).'</>';
        };

        try {
            $messages[] = $separator('gray');
        } catch (\Exception $e) {
            $messages[] = $separator('blue');
        }

        $header = '<options=bold;fg=yellow> '.array_sum($stats).' entities are checked in:</>';
        $types = self::presentTypes($typesStats);

        $max = self::getMaxNamespaceLength($autoload);
        $messages[] = $header.'  '.$types;
        $messages[] = '';

        foreach ($autoload as $composerPath => $psr4) {
            $output = '';
            $messages[] = ' <fg=blue>./'.trim($composerPath.'/', '/').'composer.json </>';
            foreach ($psr4 as $namespace => $path) {
                $count = $stats[$namespace] ?? 0;
                $output .= '  '.str_pad($count, 4).' - <fg=red>'.$namespace.str_repeat(' ', $max - strlen($namespace)).' </> (<fg=green>./'.$path."</>)\n";
            }
            $messages[] = $output;
        }

        $messages[] = 'Finished In: <fg=blue>'.$time.'(s)</>';

        return $messages;
    }

    public static function noErrorFound($time)
    {
        return [
            [PHP_EOL.'<fg=green>All namespaces are correct!</><fg=blue> You rock  \(^_^)/ </>', 'line'],
            ['<fg=red;options=bold>'.$time.'(s)</>', 'line'],
            ['', 'line'],
        ];
    }

    public static function getErrorsCount($errorCount, $time)
    {
        if ($errorCount) {
            return [[PHP_EOL.$errorCount.' error(s) found.', 'warn']];
        } else {
            return CheckPsr4Printer::noErrorFound($time);
        }
    }

    public static function fixedNamespace($path, $wrong, $correct, $lineNumber = 4)
    {
        /**
         * @var $p ErrorPrinter
         */
        $p = app(ErrorPrinter::class);
        $key = 'badNamespace';
        $header = 'Incorrect namespace: '.$p->color("namespace $wrong;");
        $errorData = '  namespace fixed to:  '.$p->color("namespace $correct;");

        $p->addPendingError($path, $lineNumber, $key, $header, $errorData);
    }

    public static function wrongFileName($path, $class, $file)
    {
        /**
         * @var $p ErrorPrinter
         */
        $p = app(ErrorPrinter::class);
        $key = 'badFileName';
        $header = 'The file name and the class name are different.';
        $errorData = 'Class name: <fg=blue>'.$class.'</>'.PHP_EOL.'   File name:  <fg=blue>'.$file.'</>';

        $p->addPendingError($path, 1, $key, $header, $errorData);
    }

    private static function getMaxNamespaceLength($autoload): int
    {
        $max = 0;

        foreach ($autoload as $psr4) {
            foreach ($psr4 as $namespace => $path) {
                $max = max($max, strlen($namespace));
            }
        }

        return $max;
    }

    private static function presentTypes($typesStats)
    {
        $types = '';
        foreach ($typesStats as $type => $count) {
            $types .= ' / '.$count.' <fg=blue>'.$type.'</>';
        }
        $types .= ' /';

        return $types;
    }
}
