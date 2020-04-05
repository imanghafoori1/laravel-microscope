<?php

namespace Imanghafoori\LaravelSelfTest;

use ReflectionClass;
use ReflectionException;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class DiscoverClasses
{
    protected static $fixedNamespaces = [];

    /**
     * Get all of the events and listeners by searching the given listener directory.
     *
     * @param  string  $path
     * @param  string  $namespace
     *
     * @return void
     */
    public static function within($path, $namespace)
    {
        static::checkAllClasses((new Finder)->files()->in(base_path($path)), base_path(), $path, $namespace);
    }

    public static function import($path, $namespace)
    {
        static::checkImports((new Finder)->files()->in(base_path($path)), base_path(), $path, $namespace);
    }

    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $classes
     * @param  string  $basePath
     *
     * @param $composerPath
     * @param $composerNamespace
     *
     * @return void
     */
    protected static function checkImports($classes, $basePath, $composerPath, $composerNamespace)
    {
        foreach ($classes as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();

            if (! self::hasOpeningTag($absFilePath)) {
                continue;
            }

            [
                $currentNamespace,
                $class,
                $type,
            ] = GetClassProperties::fromFilePath($absFilePath);
            // it means that, there is no class/trait definition found in the file.
            if (! $class) {
                continue;
            }

            $tokens = token_get_all(file_get_contents($absFilePath));
            $nonImportedClasses = ParseUseStatement::findClassReferences($tokens);
            foreach ($nonImportedClasses as $nonImportedClass) {
                if (! class_exists($nonImportedClass['class']) && ! interface_exists($nonImportedClass['class'])) {
                    app(ErrorPrinter::class)->print('Used class does not exist...');
                    app(ErrorPrinter::class)->print($absFilePath);
                    app(ErrorPrinter::class)->print($nonImportedClass['class']);
                    app(ErrorPrinter::class)->print('line: ' . $nonImportedClass['line']);
                    app(ErrorPrinter::class)->print('---------------------');
                }
            }

            try {
                $classPath = trim(Str::replaceFirst($basePath, '', $absFilePath), DIRECTORY_SEPARATOR);

                $correctNamespace = self::calculateCorrectNamespace($classPath, $composerPath, $composerNamespace);

                if (self::hasOpeningTag($absFilePath)) {
                    $ref = new ReflectionClass($correctNamespace.'\\'.$class);
                    self::checkImportedClasses($ref);
                    ModelRelations::checkModelsRelations($correctNamespace.'\\'.$class, $ref);
                }
            } catch (ReflectionException $e) {

            }
        }
    }

    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $classes
     * @param  string  $basePath
     *
     * @param $composerPath
     * @param $composerNamespace
     *
     * @return void
     */
    protected static function checkAllClasses($classes, $basePath, $composerPath, $composerNamespace)
    {
        foreach ($classes as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();
            $classPath = trim(Str::replaceFirst($basePath, '', $absFilePath), DIRECTORY_SEPARATOR);
            if (! self::hasOpeningTag($absFilePath)) {
                app(ErrorPrinter::class)->print('Skipped file: ' .$classPath);
                continue;
            }

            [
                $currentNamespace,
                $class,
                $type,
            ] = GetClassProperties::fromFilePath($absFilePath);

            // it means that, there is no class/trait definition found in the file.
            if (! $class) {
                app(ErrorPrinter::class)->print('skipped file: ' .$classPath);
                continue;
            }

            try {
                $correctNamespace = self::calculateCorrectNamespace($classPath, $composerPath, $composerNamespace);

                if ($currentNamespace !== $correctNamespace) {
                    self::errorOut($classPath, $correctNamespace, $currentNamespace);
                    self::correctNamespace($absFilePath, $currentNamespace, $correctNamespace);
                }
            } catch (ReflectionException $e) {

            }
        }
    }

    public static function hasOpeningTag(string $file)
    {
        $fp = fopen($file, 'r');

        if (feof($fp)) {
            return false;
        }

        $buffer = fread($fp, 20);

        $result = strpos($buffer, '<?php') !== false;

        fclose($fp);

        return $result;
    }

    /**
     * Calculate the namespace\className from absolute file path.
     *
     * @param  string  $filePath
     * @param  string  $basePath
     *
     * @param $path
     * @param $rootNamespace
     *
     * @return string
     */
    protected static function calculateClassFromFile($filePath, $basePath, $path, $rootNamespace)
    {
        $class = trim(Str::replaceFirst($basePath, '', $filePath), DIRECTORY_SEPARATOR);

        // remove .php from class path
        $withoutDotPhp = Str::replaceLast('.php', '', $class);
        // ensure backslash on windows
        $allBackSlash = str_replace(DIRECTORY_SEPARATOR, '\\', $withoutDotPhp);

        // replaces the base folder name with corresponding namespace
        return str_replace(rtrim($path, '/').'\\', $rootNamespace, $allBackSlash);
    }

    private static function checkImportedClasses(ReflectionClass $classReflection)
    {
        $imports = ParseUseStatement::getUseStatements($classReflection);
        foreach ($imports as $i => $imp) {
            if (self::exists($imp[0])) {
                self::wrongImport($classReflection->getName(), $imp);
            }
        }
    }

    /**
     * @param  string  $classFilePath
     * @param  string  $incorrectNamespace
     * @param  string  $correctNamespace
     */
    protected static function correctNamespace($classFilePath, string $incorrectNamespace, string $correctNamespace)
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

    /**
     * @param  string  $classPath
     * @param  string  $correctNamespace
     */
    protected static function errorOut(string $classPath, string $correctNamespace, $incorrectNamespace)
    {
        app(ErrorPrinter::class)->print(' - Incorrect namespace: '.$incorrectNamespace);
        app(ErrorPrinter::class)->print($classPath);
        app(ErrorPrinter::class)->print('It should be:   namespace '.$correctNamespace.';  ');
    }

    protected static function calculateCorrectNamespace($classPath, $path, $rootNamespace)
    {
        $p = explode(DIRECTORY_SEPARATOR, $classPath);
        array_pop($p);
        $p = implode('\\', $p);

        return str_replace(trim($path, '\\//'), trim($rootNamespace, '\\/'), $p);
    }

    /**
     * @param $imp
     *
     * @return bool
     */
    private static function exists($imp)
    {
        return ! class_exists($imp) && ! interface_exists($imp) && ! trait_exists($imp);
    }

    /**
     * @param  string  $err
     * @param $imp
     */
    private static function wrongImport(string $err, $imp)
    {
        app(ErrorPrinter::class)->print(' - Wrong import');
        app(ErrorPrinter::class)->print($err);
        app(ErrorPrinter::class)->print('line: '.$imp[1].'     use '.$imp[0].';');
        app(ErrorPrinter::class)->print('/********************************************/');
    }

    /**
     * @param $basePath
     * @param $path
     * @param $rootNamespace
     * @param $_path
     * @param $incorrectNamespace
     */
    protected static function handleMissingClass($basePath, $path, $rootNamespace, $_path, $incorrectNamespace)
    {

        /*static::$fixedNamespaces[$incorrectNamespace] = [
            'class' => $class,
            'correct_namespace' => $correctNamespace
        ];*/

        app(ErrorPrinter::class)->print('/********************************************/');
    }
}
