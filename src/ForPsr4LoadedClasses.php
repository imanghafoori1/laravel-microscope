<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\LaravelPaths\FilePath;

class ForPsr4LoadedClasses
{
    /**
     * @var array
     */
    public static $allNamespaces = [];

    public static $checkedFilesNum = 0;

    public static function check($checks)
    {
        $psr4 = ComposerJson::readAutoload();

        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            foreach ($files as $phpFilePath) {
                self::$checkedFilesNum++;
                $absFilePath = $phpFilePath->getRealPath();

                $tokens = token_get_all(file_get_contents($absFilePath));

                foreach ($checks as $check) {
                    $check::check($tokens, $absFilePath, $phpFilePath, $psr4Path, $psr4Namespace);
                }
            }
        }
    }

    public static function classList()
    {
        $psr4 = ComposerJson::readAutoload();

        if (self::$allNamespaces) {
            return self::$allNamespaces;
        }

        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);

            foreach ($files as $classFilePath) {
                $fileName = $classFilePath->getFilename();
                if (Str::endsWith($fileName, ['.blade.php'])) {
                    continue;
                }

                $relativePath = \str_replace(base_path(), '', $classFilePath->getRealPath());

                $composerPath = \str_replace('/', '\\', $psr4Path);

                // replace composer base_path with composer namespace
                /**
                 *  "psr-4": {
                 *      "App\\": "app/"
                 *  }
                 */
                // calculate namespace
                $ns = Str::replaceFirst(\trim($composerPath, '\\'), \trim($psr4Namespace, '\\/'), $relativePath);
                $t = \str_replace('.php', '', [$ns, $fileName]);
                $t = \str_replace('/', '\\', $t); // for linux environments.

                self::$allNamespaces[$t[1]][] = \trim($t[0], '\\');
            }
        }

        return self::$allNamespaces;
    }
}
