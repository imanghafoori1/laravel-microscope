<?php

namespace Imanghafoori\LaravelMicroscope;

use ImanGhafoori\ComposerJson\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\TokenAnalyzer\Str;

class ForPsr4LoadedClasses
{
    /**
     * @var array<string, array>
     */
    public static $allNamespaces = [];

    /**
     * @var int
     */
    public static $checkedFilesNum = 0;

    public static function check($checks, $params = [], $includeFile = '', $includeFolder = '')
    {
        $stats = [];
        foreach (Analyzers\ComposerJson::readAutoload() as $composerPath => $psr4) {
            foreach ($psr4 as $psr4Namespace => $psr4Paths) {
                foreach ((array) $psr4Paths as $psr4Path) {
                    foreach (FilePath::getAllPhpFiles($psr4Path) as $phpFilePath) {
                        $absFilePath = $phpFilePath->getRealPath();
                        if (! FilePath::contains($absFilePath, $includeFile, $includeFolder)) {
                            continue;
                        }
                        $stats[$composerPath][$psr4Namespace][$psr4Path] = 1 + ($stats[$composerPath][$psr4Namespace][$psr4Path] ?? 0);
                        self::$checkedFilesNum++;
                        $tokens = token_get_all(file_get_contents($absFilePath));

                        $params1 = (! is_array($params) && is_callable($params)) ? $params($tokens, $absFilePath, $psr4Path, $psr4Namespace) : $params;
                        foreach ($checks as $check) {
                            try {
                                $newTokens = $check::check($tokens, $absFilePath, $phpFilePath, $psr4Path, $psr4Namespace, $params1);
                                if ($newTokens) {
                                    $tokens = $newTokens;
                                    $params1 = (! is_array($params) && is_callable($params)) ? $params($tokens, $absFilePath, $psr4Path, $psr4Namespace) : $params;
                                }
                            } catch (\Throwable $e) {
                                $msg = $e->getMessage();
                                if (Str::startsWith($msg, ['Interface \'', 'Class \'', 'Trait \'']) && Str::endsWith($msg, ' not found')) {
                                    app(ErrorPrinter::class)->simplePendError(
                                        $msg, $e->getFile(), $e->getLine(), 'error', get_class($e), ''
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }

        return $stats;
    }

    public static function classList()
    {
        if (self::$allNamespaces) {
            return self::$allNamespaces;
        }

        foreach (self::getCandidateSearchPaths() as $baseComposerPath => $psr4) {
            foreach ($psr4 as $folder => $psr4Mappings) {
                foreach ((array) $psr4Mappings as $namespace => $_psr4Paths) {
                    foreach ((array) $_psr4Paths as $psr4Path) {
                        self::calculate($psr4Path, $baseComposerPath, $namespace);
                    }
                }
            }
        }

        return self::$allNamespaces;
    }

    private static function calculate($psr4Path, $baseComposerPath, $namespace): void
    {
        foreach (FilePath::getAllPhpFiles($psr4Path, $baseComposerPath) as $classFilePath) {
            $fileName = $classFilePath->getFilename();
            if (\substr_count($fileName, '.') > 1) {
                continue;
            }

            $relativePath = \str_replace($baseComposerPath ?: base_path(), '', $classFilePath->getRealPath());

            [$classBaseName, $fullClassPath] = self::derive($psr4Path, $relativePath, $namespace, $fileName);
            self::$allNamespaces[$classBaseName][] = $fullClassPath;
        }
    }

    public static function derive($psr4Path, $relativePath, $namespace, $fileName): array
    {
        $composerPath = \str_replace('/', '\\', $psr4Path);
        $relativePath = \str_replace('/', '\\', $relativePath);

        /**
         * // replace composer base_path with composer namespace
         *  "psr-4": {
         *      "App\\": "app/"
         *  }.
         */
        // calculate namespace
        $ns = Str::replaceFirst(\trim($composerPath, '\\'), \trim($namespace, '\\/'), $relativePath);
        $t = \str_replace('.php', '', [$ns, $fileName]);
        $t = \str_replace('/', '\\', $t); // for linux environments.

        $classBaseName = $t[1];
        $fullClassPath = $t[0];

        return [$classBaseName, \trim($fullClassPath, '\\')];
    }

    private static function getCandidateSearchPaths()
    {
        $sp = DIRECTORY_SEPARATOR;
        $path1 = base_path();
        $path2 = base_path('vendor'.$sp.'laravel'.$sp.'framework');

        return [
            $path1 => Analyzers\ComposerJson::make()->readAutoload(),
            $path2 => ComposerJson::make($path2)->readAutoload(),
        ];
    }
}
