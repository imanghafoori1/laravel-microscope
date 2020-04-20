<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class NamespaceCorrector
{
    /**
     * @param  string  $classFilePath
     * @param  string  $incorrectNamespace
     * @param  string  $correctNamespace
     */
    public static function fix($classFilePath, $incorrectNamespace, $correctNamespace)
    {
        // decides to add namespace (in case there is no namespace) or edit the existing one.
        [$oldLine, $newline] = self::getNewLine($incorrectNamespace, $correctNamespace);

        $oldLine = ltrim($oldLine, '\\');
        ReplaceLine::replaceFirst($classFilePath, $oldLine, $newline);
    }

    /**
     * @param  string  $relativeClassPath
     * @param  string  $composerPath
     * @param  string  $rootNamespace
     *
     * @return string
     */
    public static function calculateCorrectNamespace($relativeClassPath, $composerPath, $rootNamespace)
    {
        // remove the filename.php from the end of the string
        $p = explode(DIRECTORY_SEPARATOR, $relativeClassPath);
        array_pop($p);
        // ensure back slashes.
        $p = implode('\\', $p);

        $composerPath = str_replace('/', '\\', $composerPath);

        // replace composer base_path with composer namespace
        /**
         *  "psr-4": {
         *      "App\\": "app/"
         *  }.
         */
        return str_replace(trim($composerPath, '\\'), trim($rootNamespace, '\\/'), $p);
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

        return [$incorrectNamespace, $newline];
    }

    public static function getRelativePathFromNamespace($namespace)
    {
        $autoload = Util::parseComposerJson('autoload.psr-4');
        $namespaces = array_keys($autoload);
        $paths = array_values($autoload);

        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, str_replace($namespaces, $paths, $namespace));
    }
}
