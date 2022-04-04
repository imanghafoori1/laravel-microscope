<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

use Imanghafoori\Filesystem\FileManipulator;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\SearchReplace\Searcher;
use Imanghafoori\TokenAnalyzer\Str;

class NamespaceCorrector
{
    public static function getNamespaceFromFullClass($class)
    {
        $segments = explode('\\', $class);
        array_pop($segments); // removes the last part

        return trim(implode('\\', $segments), '\\');
    }

    public static function haveSameNamespace($class1, $class2)
    {
        return self::getNamespaceFromFullClass($class1) === self::getNamespaceFromFullClass($class2);
    }

    public static function fix($classFilePath, $incorrectNamespace, $correctNamespace)
    {
        // decides to add namespace (in case there is no namespace) or edit the existing one.
        [$oldLine, $newline] = self::getNewLine($incorrectNamespace, $correctNamespace);
        $oldLine = \ltrim($oldLine, '\\');

        $tokens = token_get_all(file_get_contents($classFilePath));
        if ($oldLine !== '<?php') {
            // replacement
            [$newVersion, $lines] = Searcher::searchReplace([
                'fix' => [
                    'search' => 'namespace '.$oldLine.';',
                    'replace' => 'namespace '.$newline.';',
                ],
            ], $tokens);
            Filesystem::$fileSystem::file_put_contents($classFilePath, $newVersion);
        } elseif ($tokens[2][0] !== T_DECLARE) {
            // insertion
            FileManipulator::replaceFirst($classFilePath, $oldLine, '<?php'.PHP_EOL.PHP_EOL.$newline);
        } else {
            // inserts after declare
            $i = 2;
            while ($tokens[$i++] !== ';') {
            }
            FileManipulator::insertNewLine($classFilePath, PHP_EOL.$newline, $tokens[$i][2] + 1);
        }
    }

    public static function calculateCorrectNamespace($relativeClassPath, $composerPath, $rootNamespace)
    {
        $classPath = \explode(DIRECTORY_SEPARATOR, $relativeClassPath);
        // Removes the filename
        array_pop($classPath);

        $classPath = \implode('\\', $classPath);

        // Ensure back slashes in All Operating Systems.
        $composerPath = \str_replace('/', '\\', $composerPath);

        // replace composer base_path with composer namespace
        /**
         *  "psr-4": {
         *      "App\\": "app/"
         *  }.
         */
        return Str::replaceFirst(\trim($composerPath, '\\'), \trim($rootNamespace, '\\/'), $classPath);
    }

    private static function getNewLine($incorrectNamespace, $correctNamespace)
    {
        if ($incorrectNamespace) {
            return [$incorrectNamespace, $correctNamespace];
        }

        // In case there is no namespace specified in the file:
        return ['<?php', 'namespace '.$correctNamespace.';'.PHP_EOL];
    }

    public static function getRelativePathFromNamespace($namespace, $autoload = null)
    {
        [$namespaces, $paths] = self::getSortedAutoload($autoload);
        [$namespaces, $paths] = self::flatten($paths, $namespaces);

        return \str_replace(['\\', '/'], DIRECTORY_SEPARATOR, \str_replace($namespaces, $paths, $namespace));
    }

    public static function getNamespacedClassFromPath($path, $autoload = null)
    {
        [$namespaces, $paths] = self::getSortedAutoload($autoload);

        // Remove .php from class path
        $relPath = str_replace([base_path(), '.php'], '', $path);
        $relPath = \str_replace('\\', '/', $relPath);

        [$_namespaces, $_paths] = self::flatten($paths, $namespaces);

        return trim(\str_replace('/', '\\', \str_replace($_paths, $_namespaces, $relPath)), '\\');
    }

    private static function getSortedAutoload($autoload): array
    {
        ($autoload === null) && $autoload = ComposerJson::readAutoload();

        uasort($autoload, function ($path, $path2) {
            return strlen($path2) <=> strlen($path);
        });

        $namespaces = array_keys($autoload);
        $paths = array_values($autoload);

        return [$namespaces, $paths];
    }

    private static function flatten($paths, $namespaces)
    {
        $_namespaces = [];
        $_paths = [];
        $counter = 0;
        foreach ($paths as $k => $_p) {
            foreach ((array) $_p as $p) {
                $counter++;
                $_namespaces[$counter] = $namespaces[$k];
                $_paths[$counter] = $p;
            }
        }

        return [$_namespaces, $_paths];
    }
}
