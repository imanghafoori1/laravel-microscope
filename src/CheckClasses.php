<?php

namespace Imanghafoori\LaravelSelfTest;

use ReflectionClass;
use ReflectionException;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class CheckClasses
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

    public static function import($namespace, $path)
    {
        static::checkImports((new Finder)->files()->in(base_path($path)), base_path(), $path, $namespace);
    }

    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $files
     * @param  string  $basePath
     *
     * @param $composerPath
     * @param $composerNamespace
     *
     * @return void
     */
    protected static function checkImports($files, $basePath, $composerPath, $composerNamespace)
    {
        foreach ($files as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();

            if (! self::hasOpeningTag($absFilePath)) {
                continue;
            }

            [
                $currentNamespace,
                $class,
                $type,
            ] = GetClassProperties::fromFilePath($absFilePath);

            // It means that, there is no class/trait definition found in the file.
            if (! $class) {
                continue;
            }

            $tokens = token_get_all(file_get_contents($absFilePath));
            $nonImportedClasses = ParseUseStatement::findClassReferences($tokens);
            foreach ($nonImportedClasses as $nonImportedClass) {
                if (! class_exists($nonImportedClass['class'])) {
                    app(ErrorPrinter::class)->wrongUsedClassError($absFilePath, $nonImportedClass);
                }
            }

            try {
                $classPath = trim(Str::replaceFirst($basePath, '', $absFilePath), DIRECTORY_SEPARATOR);

                $correctNamespace = NamespaceCorrector::calculateCorrectNamespace($classPath, $composerPath, $composerNamespace);

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
                $correctNamespace = NamespaceCorrector::calculateCorrectNamespace($classPath, $composerPath, $composerNamespace);

                if ($currentNamespace !== $correctNamespace) {
                    app(ErrorPrinter::class)->badNamespace($classPath, $correctNamespace, $currentNamespace);
                    NamespaceCorrector::fix($absFilePath, $currentNamespace, $correctNamespace);
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
        foreach ($imports as $i => $import) {
            if (self::exists($import[0])) {
                app(ErrorPrinter::class)->wrongImport($classReflection->getName(), $import);
            }
        }
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
