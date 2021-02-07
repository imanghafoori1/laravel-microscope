<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\GetClassProperties;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\PendingError;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;

class CheckNamespaces
{
    public static $checkedNamespaces = 0;

    public static $changedNamespaces = [];

    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $paths
     * @param  $composerPath
     * @param  $composerNamespace
     * @param  $command
     *
     * @return void
     */
    public static function within($paths, $composerPath, $composerNamespace, $command)
    {
        $detailed = $command->option('detailed');
        foreach ($paths as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();

            // exclude blade files
            if (Str::endsWith($absFilePath, ['.blade.php'])) {
                continue;
            }

            // exclude migration directories
            if (Str::startsWith($absFilePath, LaravelPaths::migrationDirs())) {
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

            if ($detailed) {
                event('microscope.checking', [$classFilePath->getRelativePathname(), $command]);
            }

            $relativePath = FilePath::getRelativePath($absFilePath);
            $correctNamespace = NamespaceCorrector::calculateCorrectNamespace($relativePath, $composerPath, $composerNamespace);
            self::$checkedNamespaces++;
            if ($currentNamespace === $correctNamespace) {
                continue;
            }
            self::changedNamespaces($class, $currentNamespace, $correctNamespace);
            self::warn($currentNamespace, $relativePath, $class);

            if (! $command->option('nofix') && self::ask($command, $correctNamespace)) {
                self::doNamespaceCorrection($absFilePath, $currentNamespace, $correctNamespace);
                // maybe an event listener
                app(ErrorPrinter::class)->badNamespace($relativePath, $correctNamespace, $currentNamespace);
            }
        }
    }

    private static function warn($currentNamespace, $relativePath, $class)
    {
        /**
         * @var $p ErrorPrinter
         */
        $p = app(ErrorPrinter::class);
        $msg = 'Incorrect namespace: '.$p->yellow("namespace $currentNamespace;");
        PendingError::$maxLength = max(PendingError::$maxLength, strlen($msg));
        $p->end();
        $currentNamespace && $p->printHeader('Incorrect namespace: '.$p->yellow("namespace $currentNamespace;"));
        ! $currentNamespace && $p->printHeader('Namespace Not Found: '.$class);
        $p->printLink($relativePath, 3);
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

    private static function ask($command, $correctNamespace)
    {
        if ($command->option('force')) {
            return true;
        }

        return $command->getOutput()->confirm('Do you want to change it to: '.$correctNamespace, true);
    }

    private static function changedNamespaces($class, $currentNamespace, $correctNamespace)
    {
        $_currentClass = $currentNamespace.'\\'.$class;
        $_correctClass = $correctNamespace.'\\'.$class;
        $relPath = NamespaceCorrector::getRelativePathFromNamespace($currentNamespace);
        if (is_dir(base_path($relPath.DIRECTORY_SEPARATOR.$class))) {
            self::$changedNamespaces[$_currentClass.';'] = $_correctClass.';';
            self::$changedNamespaces[$_currentClass.'('] = $_correctClass.'(';
            self::$changedNamespaces[$_currentClass.'::'] = $_correctClass.'::';
            self::$changedNamespaces[$_currentClass.' as'] = $_correctClass.' as';
        } else {
            self::$changedNamespaces[$_currentClass] = $_correctClass;
        }
    }
}
