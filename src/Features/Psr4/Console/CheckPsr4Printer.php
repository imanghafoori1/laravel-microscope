<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\Console;

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

    private static function noErrorFound()
    {
        return [
            [PHP_EOL.'<fg=green>All namespaces are correct!</><fg=blue> You rock  \(^_^)/ </>', 'line'],
            ['', 'line'],
        ];
    }

    public static function getErrorsCount($errorCount)
    {
        if ($errorCount === 1) {
            return [[PHP_EOL.'one error was found.', 'warn']];
        } elseif ($errorCount > 1) {
            return [[PHP_EOL.$errorCount.' errors were found.', 'warn']];
        } else {
            return self::noErrorFound();
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

    private static function colorizer($str, $color)
    {
        return '<fg='.$color.'>'.$str.'</>';
    }
}
