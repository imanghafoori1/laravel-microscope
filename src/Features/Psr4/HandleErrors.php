<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Illuminate\Console\Command;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\FileReaders\Paths;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;

class HandleErrors
{
    private static $pathsForReferenceFix = [];

    /**
     * @var Command
     */
    private static $command;

    public static function handleErrors(array $errorsLists, Command $command)
    {
        self::$command = $command;
        $before = self::beforeReferenceFix();
        $after = self::afterReferenceFix();

        foreach ($errorsLists as $errors) {
            foreach ($errors as $wrong) {
                self::handleError($wrong, $before, $after);
            }
        }
    }

    private static function handleError($wrong, $beforeFix, $afterFix)
    {
        if ($wrong['type'] === 'namespace') {
            $absPath = $wrong['absFilePath'];
            $from = $wrong['currentNamespace'];
            $to = $wrong['correctNamespace'];
            $class = $wrong['class'];
            $relativePath = str_replace(base_path(), '', $absPath);

            CheckPsr4Printer::warnIncorrectNamespace($relativePath, $from, $class);

            if (CheckPsr4Printer::ask(self::$command, $to)) {
                self::updateOldRefs($absPath, $from, $to, $class, $beforeFix, $afterFix, $relativePath);
            }
        } elseif ($wrong['type'] === 'filename') {
            CheckPsr4Printer::wrongFileName($wrong['relativePath'], $wrong['class'], $wrong['fileName']);
        }
    }

    private static function afterReferenceFix()
    {
        return function ($path, $changedLineNums, $content) {
            Filesystem::$fileSystem::file_put_contents($path, $content);

            $p = ErrorPrinter::singleton();
            foreach ($changedLineNums as $line) {
                $p->simplePendError('', $path, $line, 'ns_replacement', 'Namespace replacement:');
            }
        };
    }

    private static function beforeReferenceFix()
    {
        $command = self::$command;
        if ($command->option('force-ref-fix')) {
            return function () {
                return true;
            };
        }

        return function ($path, $lineIndex, $lineContent) use ($command) {
            $command->getOutput()->writeln(ErrorPrinter::getLink($path, $lineIndex));
            $command->warn($lineContent);
            $msg = 'Do you want to update reference to the old namespace?';

            return $command->confirm($msg, true);
        };
    }

    public static function getClassMaps($basePath)
    {
        $result = [];
        foreach (ComposerJson::make()->readAutoloadClassMap() as $compPath => $classmaps) {
            foreach ($classmaps as $classmap) {
                $compPath = trim($compPath, '/') ? trim($compPath, '/').DIRECTORY_SEPARATOR : '';
                $classmap = $basePath.DIRECTORY_SEPARATOR.$compPath.$classmap;
                $classmap = array_values(ClassMapGenerator::createMap($classmap));
                $result = array_merge($classmap, $result);
            }
        }

        return $result;
    }

    private static function updateOldRefs($absPath, $from, $to, $class, $beforeFix, $afterFix, $relativePath)
    {
        NamespaceFixer::fix($absPath, $from, $to);
        $command = self::$command;

        if ($from && ! $command->option('no-ref-fix')) {
            $changes = [
                $from.'\\'.$class => $to.'\\'.$class,
            ];

            ClassRefCorrector::fixAllRefs($changes, self::getPathsForReferenceFix(), $beforeFix, $afterFix);
        }
        CheckPsr4Printer::fixedNamespace($relativePath, $from, $to);
    }

    public static function getPathsForReferenceFix()
    {
        if (self::$pathsForReferenceFix) {
            return self::$pathsForReferenceFix;
        }

        $paths = [];
        $paths['psr4'] = self::getPsr4();
        $paths['autoload_files'] = ComposerJson::readAutoloadFiles();
        $paths['class_map'] = self::getClassMaps(base_path());
        $paths['routes'] = RoutePaths::get();
        $paths['blades'] = LaravelPaths::bladeFilePaths();

        $dirs = [
            LaravelPaths::migrationDirs(),
            config_path(),
            LaravelPaths::factoryDirs(),
            LaravelPaths::seedersDir(),
        ];
        $paths = self::collectFilesInNonPsr4Paths($paths, $dirs);

        self::$pathsForReferenceFix = $paths;

        return $paths;
    }

    public static function collectFilesInNonPsr4Paths($paths, $dirs)
    {
        foreach ($dirs as $dir) {
            $paths = array_merge(Paths::getAbsFilePaths($dir), $paths);
        }

        return $paths;
    }

    private static function getPsr4()
    {
        $psr4 = [];
        foreach (ComposerJson::readAutoload() as $autoload) {
            foreach ($autoload as $psr4Path) {
                foreach (FilePath::getAllPhpFiles($psr4Path) as $file) {
                    $psr4[] = $file->getRealPath();
                }
            }
        }

        return $psr4;
    }
}
