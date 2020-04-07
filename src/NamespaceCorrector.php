<?php

namespace Imanghafoori\LaravelMicroscope;

class NamespaceCorrector
{
    /**
     * @param  string  $classFilePath
     * @param  string  $incorrectNamespace
     * @param  string  $correctNamespace
     */
    public static function fix($classFilePath, $incorrectNamespace, $correctNamespace)
    {
        $newline = 'namespace '.$correctNamespace.';'.PHP_EOL;

        // in case there is no namespace specified in the file:
        if (! $incorrectNamespace) {
            $incorrectNamespace = '<?php';
            $newline = '<?php'.PHP_EOL.PHP_EOL.$newline;
        }
        $search = ltrim($incorrectNamespace, '\\');
        ReplaceLine::replace($classFilePath, $search, $newline);

        app(ErrorPrinter::class)->print('namespace fixed to:'.$correctNamespace);
        app(ErrorPrinter::class)->end();
    }

    public static function calculateCorrectNamespace($classPath, $path, $rootNamespace)
    {
        // remove the filename.php from the end of the string
        $p = explode(DIRECTORY_SEPARATOR, $classPath);
        array_pop($p);
        $p = implode('\\', $p);

        $path = str_replace('/', '\\', $path);

        return str_replace(trim($path, '\\'), trim($rootNamespace, '\\/'), $p);
    }
}
