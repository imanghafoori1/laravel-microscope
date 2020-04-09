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
        [
            $search,
            $newline
        ] = self::getNewLine($incorrectNamespace, $correctNamespace);

        $search = ltrim($search, '\\');
        ReplaceLine::replaceFirst($classFilePath, $search, $newline);

        app(ErrorPrinter::class)->fixedNameSpace($correctNamespace);
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

    /**
     * @param $incorrectNamespace
     * @param $correctNamespace
     *
     * @return array
     */
    private static function getNewLine($incorrectNamespace, $correctNamespace)
    {
        // in case there is no namespace specified in the file:
        if (! $incorrectNamespace) {
            $incorrectNamespace = '<?php';
            $newline = '<?php'.PHP_EOL.PHP_EOL.'namespace '.$correctNamespace.';'.PHP_EOL;
        } else {
            $newline = $correctNamespace;
        }

        return [
            $incorrectNamespace,
            $newline,
        ];
    }
}
