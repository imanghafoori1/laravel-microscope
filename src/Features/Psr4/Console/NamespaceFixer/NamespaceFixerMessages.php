<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\Console\NamespaceFixer;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\PendingError;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class NamespaceFixerMessages
{
    public static $pause = 250000;

    public static function warnIncorrectNamespace(PhpFileDescriptor $file, $currentNamespace, $className)
    {
        $printer = ErrorPrinter::singleton();
        $msg = self::getHeader($currentNamespace, $className);
        self::$pause && usleep(self::$pause);

        PendingError::$maxLength = max(PendingError::$maxLength, strlen($msg) - 12);

        $printer->printHeader($msg, false);
        self::$pause && usleep(self::$pause);

        $printer->printLink($file, 3);
    }

    private static function getHeader($currentNamespace, $className): string
    {
        if ($currentNamespace) {
            $namespace = Color::blue($currentNamespace);
            $header = "Incorrect namespace: '$namespace'";
        } else {
            $header = 'Namespace Not Found for class: '.Color::blue($className);
        }

        return $header;
    }

    public static function wrongFileName($path, $class, $file)
    {
        $key = 'badFileName';
        $header = 'The file name and the class name are different.';
        $errorData = 'Class name: '.Color::blue($class).PHP_EOL.'   File name:  '.Color::blue($file);

        ErrorPrinter::singleton()->addPendingError($path, 1, $key, $header, $errorData);
    }

    public static function fixedNamespace($file, $wrong, $correct, $class, $lineNumber = 4)
    {
        $path = $file->relativePath();
        $key = 'badNamespace';
        $printer = ErrorPrinter::singleton();

        $errorData = ' Namespace of class "'.Color::yellow($class).'" fixed to:';

        $printer->addPendingError($path, $lineNumber, $key, $errorData, Color::blue($correct));
    }

    public static function wrongNamespace(PhpFileDescriptor $file, $wrong, $correct, $class, $lineNumber = 4)
    {
        $path = $file->relativePath();
        $key = 'badNamespace';
        $printer = ErrorPrinter::singleton();

        $errorData = ' Namespace of class "'.Color::yellow($wrong.'\\'.$class).'" should be:';

        $printer->addPendingError($path, $lineNumber, $key, $errorData, Color::blue($correct));
    }
}
