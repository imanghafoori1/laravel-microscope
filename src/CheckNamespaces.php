<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\GetClassProperties;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;
use Imanghafoori\LaravelMicroscope\Contracts\FileCheckContract as FileCheckContractAlias;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckNamespaces
{
    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $paths
     * @param $composerPath
     * @param $composerNamespace
     * @param  FileCheckContractAlias  $fileCheckContract
     *
     * @return void
     */
    public static function forNamespace($paths, $composerPath, $composerNamespace, $fileCheckContract)
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
                $parent,
            ] = GetClassProperties::fromFilePath($absFilePath);

            // skip if there is no class/trait/interface definition found.
            // for example a route file or a config file.
            if (! $class || $parent == 'Migration') {
                continue;
            }

            $fileCheckContract->onFileTap($classFilePath);

            $relativePath = FilePath::getRelativePath($absFilePath);
            $correctNamespace = NamespaceCorrector::calculateCorrectNamespace($relativePath, $composerPath, $composerNamespace);
            if ($currentNamespace === $correctNamespace) {
                continue;
            }

            self::warn($currentNamespace, $relativePath);

            $answer = self::ask($fileCheckContract, $correctNamespace);
            if ($answer) {
                self::doNamespaceCorrection($absFilePath, $currentNamespace, $correctNamespace);
                // maybe an event listener
                app(ErrorPrinter::class)->badNamespace($relativePath, $correctNamespace, $currentNamespace);
            }
        }
    }

    private static function warn($currentNamespace, $relativePath)
    {
        $p = app(ErrorPrinter::class);
        $p->printHeader('Incorrect namespace: '.$p->yellow("namespace $currentNamespace;"));
        $p->printLink($relativePath, 4);
    }

    public static function hasOpeningTag($file)
    {
        $fp = fopen($file, 'r');

        if (feof($fp)) {
            return false;
        }

        $buffer = fread($fp, 20);
        fclose($fp);

        return Str::startsWith($buffer, '<?php');
    }

    protected static function doNamespaceCorrection($absFilePath, $currentNamespace, $correctNamespace)
    {
        event('laravel_microscope.namespace_fixing', get_defined_vars());
        NamespaceCorrector::fix($absFilePath, $currentNamespace, $correctNamespace);
        event('laravel_microscope.namespace_fixed', get_defined_vars());
    }

    private static function migrationPaths()
    {
        // normalize the migration paths
        $migrationDirs = [];

        foreach (app('migrator')->paths() as $path) {
            $migrationDirs[] = FilePath::normalize($path);
        }

        return $migrationDirs;
    }

    private static function ask($fileCheckContract, $correctNamespace)
    {
        return $fileCheckContract->getOutput()->confirm('Do you want to change it to: '.$correctNamespace, true);
    }
}
