<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\PendingError;

class CheckPsr4Printer extends ErrorPrinter
{
    public static function warnIncorrectNamespace($path, $currentNamespace, $className)
    {
        $printer = ErrorPrinter::singleton();
        $msg = self::getHeader($currentNamespace, $className);

        PendingError::$maxLength = max(PendingError::$maxLength, strlen($msg) - 12);
        $printer->end();

        $printer->printHeader($msg);
        $printer->printLink($path, 3);
    }

    private static function getHeader($currentNamespace, $className): string
    {
        if ($currentNamespace) {
            $namespace = self::colorizer("$currentNamespace", 'blue');
            $header = "Incorrect namespace: '$namespace'";
        } else {
            $header = 'Namespace Not Found for class: "'.self::colorizer($className, 'blue').'"';
        }

        return $header;
    }

    public static function reportResult($autoload, $time, TypeStatistics $typesStats)
    {
        $messages = [];

        $messages[] = ErrorPrinter::lineSeparator();
        $messages[] = self::getHeaderLine($typesStats);
        $messages[] = '';

        $max = self::getMaxNamespaceLength($autoload);

        foreach ($autoload as $composerPath => $psr4) {
            $messages[] = self::getComposerFileAddress($composerPath);
            $messages[] = self::getNamespaces($psr4, $typesStats, $max);
        }

        $messages[] = self::getFinishMsg($time);

        return $messages;
    }

    public static function noErrorFound()
    {
        return [
            [PHP_EOL.'<fg=green>All namespaces are correct!</><fg=blue> You rock  \(^_^)/ </>', 'line'],
            ['', 'line'],
        ];
    }

    public static function getErrorsCount($errorCount)
    {
        if ($errorCount) {
            return [[PHP_EOL.$errorCount.' error(s) found.', 'warn']];
        } else {
            return CheckPsr4Printer::noErrorFound();
        }
    }

    public static function fixedNamespace($file, $wrong, $correct, $class, $lineNumber = 4)
    {
        $path = $file->relativePath();
        $key = 'badNamespace';
        $printer = ErrorPrinter::singleton();

        $errorData = '  Namespace of class "'.$class.'" fixed to: '.$printer->color($correct);

        $printer->addPendingError($path, $lineNumber, $key, $errorData, '');
    }

    public static function wrongFileName($path, $class, $file)
    {
        $key = 'badFileName';
        $header = 'The file name and the class name are different.';
        $errorData = 'Class name: <fg=blue>'.$class.'</>'.PHP_EOL.'   File name:  <fg=blue>'.$file.'</>';

        ErrorPrinter::singleton()->addPendingError($path, 1, $key, $header, $errorData);
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

    private static function presentTypes(TypeStatistics $typesStats)
    {
        $results = $typesStats->iterate(function ($type, $count) {
            return " | $count <fg=blue>$type</>";
        });

        return implode('', $results).' |';
    }

    private static function header($stats): string
    {
        return "<options=bold;fg=yellow> $stats entities are checked in:</>";
    }

    private static function detailLine(int $count, string $namespace, int $max, string $path): string
    {
        $spacing = str_repeat(' ', $max - strlen($namespace));
        $paddedCount = str_pad($count, 4);

        $path = self::colorizer("./$path", 'green');
        // Since the namespace ends with a back-slash
        // we have to include a space char so that
        // the '</>' does not get scaped out.
        $namespace = self::colorizer($namespace.' ', 'red');

        return "  $paddedCount - $namespace $spacing ($path)\n";
    }

    private static function colorizer($str, $color)
    {
        return '<fg='.$color.'>'.$str.'</>';
    }

    private static function getNamespaces($psr4, TypeStatistics $typesStats, int $max): string
    {
        $output = '';

        foreach ($psr4 as $namespace => $path) {
            $count = $typesStats->namespaceCount[$namespace] ?? 0;
            $path = implode(', ', (array) $path);
            $output .= self::detailLine($count, $namespace, $max, $path);
        }

        return $output;
    }

    private static function getComposerFileAddress($composerPath): string
    {
        return ' <fg=blue>./'.trim($composerPath.'/', '/').'composer.json </>';
    }

    private static function getHeaderLine(TypeStatistics $typesStats): string
    {
        $header = self::header($typesStats->getTotalCount());
        $types = self::presentTypes($typesStats);

        return $header.'  '.PHP_EOL.$types;
    }

    private static function getFinishMsg($time): string
    {
        return 'Finished In: <fg=blue>'.$time.'(s)</>';
    }
}
