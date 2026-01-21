<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\Console\NamespaceFixer;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\PendingError;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class NamespaceFixerMessages
{
    public static $pause = 250000;

    public static function warnIncorrectNamespace(PhpFileDescriptor $file, $currentNamespace, $className)
    {
        $printer = ErrorPrinter::singleton();
        $msg = self::getHeader($currentNamespace, $className);
        usleep(self::$pause);

        PendingError::$maxLength = max(PendingError::$maxLength, strlen($msg) - 12);

        $printer->printHeader($msg, false);
        usleep(self::$pause);

        $printer->printLink($file, 3);
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

    private static function colorizer($str, $color)
    {
        return '<fg='.$color.'>'.$str.'</>';
    }

    public static function wrongFileName($path, $class, $file)
    {
        $key = 'badFileName';
        $header = 'The file name and the class name are different.';
        $errorData = 'Class name: <fg=blue>'.$class.'</>'.PHP_EOL.'   File name:  <fg=blue>'.$file.'</>';

        ErrorPrinter::singleton()->addPendingError($path, 1, $key, $header, $errorData);
    }

    public static function fixedNamespace($file, $wrong, $correct, $class, $lineNumber = 4)
    {
        $path = $file->relativePath();
        $key = 'badNamespace';
        $printer = ErrorPrinter::singleton();

        $errorData = ' Namespace of class "'.$printer->color($class, 'yellow').'" fixed to:';

        $printer->addPendingError($path, $lineNumber, $key, $errorData, $printer->color($correct));
    }

    public static function wrongNamespace(PhpFileDescriptor $file, $wrong, $correct, $class, $lineNumber = 4)
    {
        $path = $file->relativePath();
        $key = 'badNamespace';
        $printer = ErrorPrinter::singleton();

        $errorData = ' Namespace of class "'.$printer->color($wrong.'\\'.$class, 'yellow').'" should be:';

        $printer->addPendingError($path, $lineNumber, $key, $errorData, $printer->color($correct));
    }
}
