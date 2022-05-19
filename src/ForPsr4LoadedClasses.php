<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;

class ForPsr4LoadedClasses
{
    /**
     * @var array
     */
    public static $allNamespaces = [];

    public static $checkedFilesNum = 0;

    public static function check($checks, $params = [])
    {
        $psr4 = ComposerJson::readAutoload();

        $printer = app(ErrorPrinter::class);
        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            foreach ($files as $phpFilePath) {
                self::$checkedFilesNum++;
                $absFilePath = $phpFilePath->getRealPath();

                foreach ($checks as $check) {
                    $relPath = FilePath::getRelativePath($absFilePath);
                    if (cache()->has($check.$relPath.filemtime($absFilePath))) {
                        continue;
                    }
                    $tokens = token_get_all(file_get_contents($absFilePath));
                    $count1 = $printer->count;
                    $check::check($tokens, $absFilePath, $phpFilePath, $psr4Path, $psr4Namespace, $params);
                    $count2 = $printer->count;
                    $count1 === $count2 && cache()->put($check.$relPath.filemtime($absFilePath), '-', now()->addDays(3));
                }
            }
        }
    }

    public static function classList()
    {
        if (self::$allNamespaces) {
            return self::$allNamespaces;
        }

        $psr4 = ComposerJson::readAutoload();
        $composerFiles = [
            ComposerJson::$composerPath => $psr4,
        ];

        ComposerJson::$composerPath = base_path('vendor'.DIRECTORY_SEPARATOR.'laravel'.DIRECTORY_SEPARATOR.'framework');
        $psr4_ = ComposerJson::readAutoload();
        $composerFiles[ComposerJson::$composerPath] = $psr4_;
        ComposerJson::$composerPath = null;

        foreach ($composerFiles as $baseComposerPath => $psr4) {
            foreach ($psr4 as $psr4Namespace => $psr4Paths) {
                foreach ((array) $psr4Paths as $psr4Path) {
                    $files = FilePath::getAllPhpFiles($psr4Path, $baseComposerPath);

                    foreach ($files as $classFilePath) {
                        $fileName = $classFilePath->getFilename();
                        if (Str::endsWith($fileName, ['.blade.php'])) {
                            continue;
                        }

                        $relativePath = \str_replace($baseComposerPath ?: base_path(), '', $classFilePath->getRealPath());

                        $composerPath = \str_replace('/', '\\', $psr4Path);
                        $relativePath = \str_replace('/', '\\', $relativePath);

                        // replace composer base_path with composer namespace
                        /**
                         *  "psr-4": {
                         *      "App\\": "app/"
                         *  }.
                         */
                        // calculate namespace
                        $ns = Str::replaceFirst(\trim($composerPath, '\\'), \trim($psr4Namespace, '\\/'), $relativePath);
                        $t = \str_replace('.php', '', [$ns, $fileName]);
                        $t = \str_replace('/', '\\', $t); // for linux environments.

                        $classBaseName = $t[1];
                        $fullClassPath = $t[0];
                        self::$allNamespaces[$classBaseName][] = \trim($fullClassPath, '\\');
                    }
                }
            }
        }

        return self::$allNamespaces;
    }
}
