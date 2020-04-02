<?php

namespace Imanghafoori\LaravelSelfTest;

class NamespaceCorrector
{
    /**
     * @param  string  $classFilePath
     * @param  string  $incorrectNamespace
     * @param  string  $correctNamespace
     */
    public static function fix($classFilePath, string $incorrectNamespace, string $correctNamespace)
    {
        $newline = "namespace ".$correctNamespace.';'.PHP_EOL;

        // in case there is no namespace specified in the file:
        if (! $incorrectNamespace) {
            $incorrectNamespace = '<?php';
            $newline = '<?php'.PHP_EOL.PHP_EOL.$newline;
        }
        $search = ltrim($incorrectNamespace, '\\');
        ReplaceLine::replace($classFilePath, $search, $newline);

        app(ErrorPrinter::class)->print('namespace fixed to:'. $correctNamespace);
    }

    static function calculateCorrectNamespace($classPath, $path, $rootNamespace)
    {
        $p = explode(DIRECTORY_SEPARATOR, $classPath);
        array_pop($p);
        $p = implode('\\', $p);

        return str_replace(trim($path, '\\//'), trim($rootNamespace, '\\/'), $p);
    }
}
