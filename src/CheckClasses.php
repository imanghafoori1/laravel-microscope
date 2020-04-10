<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Contracts\FileCheckContract;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder;

class CheckClasses
{
    protected static $fixedNamespaces = [];

    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $files
     * @param  string  $basePath
     *
     * @param $composerPath
     * @param $composerNamespace
     *
     * @param  FileCheckContract  $fileCheckContract
     *
     * @return void
     */
    public static function checkImports($files, $basePath, $composerPath, $composerNamespace, FileCheckContract $fileCheckContract)
    {
        foreach ($files as $classFilePath) {

            if($fileCheckContract)
                $fileCheckContract->onFileTap($classFilePath);

            $absFilePath = $classFilePath->getRealPath();

            if (! self::hasOpeningTag($absFilePath)) {
                continue;
            }

            [
                $currentNamespace,
                $class,
                $type,
                $parent
            ] = GetClassProperties::fromFilePath($absFilePath);
            // It means that, there is no class/trait definition found in the file.
            if (! $class) {
                continue;
            }

            $tokens = token_get_all(file_get_contents($absFilePath));
            $nonImportedClasses = ParseUseStatement::findClassReferences($tokens, $absFilePath);
            foreach ($nonImportedClasses as $nonImportedClass) {
                $v = trim($nonImportedClass['class'], '\\');
                if (! class_exists($v) && ! trait_exists($v) && ! interface_exists($v) && ! function_exists($v)) {
                    app(ErrorPrinter::class)->wrongUsedClassError($absFilePath, $nonImportedClass);
                }
            }

            try {
//                $classPath = self::relativePath($basePath, $absFilePath);
//                $correctNamespace = NamespaceCorrector::calculateCorrectNamespace($classPath, $composerPath, $composerNamespace);

                if ($currentNamespace) {
                    $namespacedClassName = $currentNamespace.'\\'.$class;
                } else {
                    $namespacedClassName = $class;
                }

                $imports = ParseUseStatement::getUseStatementsByPath($namespacedClassName, $absFilePath);
                self::checkImportedClasses($imports, $absFilePath);

                if ($currentNamespace) {
                    $ref = new ReflectionClass($currentNamespace.'\\'.$class);
                    ModelRelations::checkModelsRelations($currentNamespace.'\\'.$class, $ref);
                } else {
                    // @todo show skipped file...
                }
            } catch (ReflectionException $e) {
                // @todo show skipped file...
            }
        }
    }

    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $paths
     * @param  string  $basePath
     *
     * @param $composerPath
     * @param $composerNamespace
     *
     * @return void
     */
    public static function checkAllClasses($paths, $composerPath, $composerNamespace)
    {
        foreach ($paths as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();

            // exclude blade files
            if (Str::endsWith($absFilePath, ['.blade.php'])) {
                continue;
            }

            // exclude migration directories
            if (Str::startsWith($absFilePath, self::migrationPaths())) {
                continue;
            }

            if (! self::hasOpeningTag($absFilePath)) {
                continue;
            }

            [
                $currentNamespace,
                $class,
                $type,
            ] = GetClassProperties::fromFilePath($absFilePath);

            // skip if there is no class/trait/interface definition found.
            // for example a route file or a config file.
            if (! $class) {
                continue;
            }

            $relativePath = self::getRelativePath($absFilePath);
            $correctNamespace = NamespaceCorrector::calculateCorrectNamespace($relativePath, $composerPath, $composerNamespace);
            self::doNamespaceCorrection($correctNamespace, $relativePath, $currentNamespace, $absFilePath);
        }
    }

    public static function hasOpeningTag($file)
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

    private static function checkImportedClasses($imports, $absPath)
    {
        foreach ($imports as $i => $import) {
            if (self::exists($import[0])) {
                app(ErrorPrinter::class)->wrongImport($absPath, $import[0], $import[1]);
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

    protected static function doNamespaceCorrection($correctNamespace, $classPath, $currentNamespace, $absFilePath)
    {
        if ($currentNamespace !== $correctNamespace) {
            app(ErrorPrinter::class)->badNamespace($classPath, $correctNamespace, $currentNamespace);
            NamespaceCorrector::fix($absFilePath, $currentNamespace, $correctNamespace);
        }
    }

    private static function migrationPaths()
    {
        $migrationDirs = [];
        foreach (app('migrator')->paths() as $path) {
            $migrationDirs[] = str_replace([
                '\\',
                '/',
            ], [
                DIRECTORY_SEPARATOR,
                DIRECTORY_SEPARATOR,
            ], $path);
        }

        return $migrationDirs;
    }

    public static function getAllPhpFiles($psr4Path)
    {
        return (new Finder)->files()->name('*.php')->in(base_path($psr4Path));
    }

    private static function getRelativePath($absFilePath)
    {
        return trim(Str::replaceFirst(base_path(), '', $absFilePath), DIRECTORY_SEPARATOR);
    }
}
