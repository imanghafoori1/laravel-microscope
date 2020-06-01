<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\LaravelPaths\MigrationPaths;
use Imanghafoori\LaravelMicroscope\ErrorReporters\PendingError;
use Imanghafoori\LaravelMicroscope\Analyzers\GetClassProperties;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckNamespaces
{
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
    public static function forNamespace($paths, $composerPath, $composerNamespace, $command)
    {
        foreach ($paths as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();

            // exclude blade files
            if (Str::endsWith($absFilePath, ['.blade.php'])) {
                continue;
            }

            // exclude migration directories
            if (Str::startsWith($absFilePath, MigrationPaths::get())) {
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

            $command->onFileTap($classFilePath->getRelativePathname());

            $relativePath = FilePath::getRelativePath($absFilePath);
            $correctNamespace = NamespaceCorrector::calculateCorrectNamespace($relativePath, $composerPath, $composerNamespace);
            if ($currentNamespace === $correctNamespace) {
                continue;
            }
            if (is_dir(base_path(NamespaceCorrector::getRelativePathFromNamespace($currentNamespace). DIRECTORY_SEPARATOR . $class))) {
                self::$changedNamespaces[$currentNamespace.'\\'. $class.';'] = $correctNamespace.'\\'. $class.';';
                self::$changedNamespaces[$currentNamespace.'\\'. $class.'('] = $correctNamespace.'\\'. $class.'(';
                self::$changedNamespaces[$currentNamespace.'\\'. $class.'::'] = $correctNamespace.'\\'. $class.'::';
                self::$changedNamespaces[$currentNamespace.'\\'. $class.' as'] = $correctNamespace.'\\'. $class.' as';
            } else {
                self::$changedNamespaces[$currentNamespace.'\\'. $class] = $correctNamespace.'\\'. $class;
            }
            self::warn($currentNamespace, $relativePath);

            $answer = self::ask($command, $correctNamespace);
            if ($answer) {
                self::doNamespaceCorrection($absFilePath, $currentNamespace, $correctNamespace);
                // maybe an event listener
                app(ErrorPrinter::class)->badNamespace($relativePath, $correctNamespace, $currentNamespace);
            }
        }
        app(ErrorPrinter::class)->counts['total'] = 0;
    }

    private static function warn($currentNamespace, $relativePath)
    {
        /**
         * @var $p ErrorPrinter
         */
        $p = app(ErrorPrinter::class);
        $msg = 'Incorrect namespace: '.$p->yellow("namespace $currentNamespace;");
        PendingError::$maxLength = max(PendingError::$maxLength, strlen($msg));
        $p->end();
        $p->printHeader('Incorrect namespace: '.$p->yellow("namespace $currentNamespace;"));
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
        return $command->getOutput()->confirm('Do you want to change it to: '.$correctNamespace, true);
    }
}
