<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;

class Psr4Classes
{
    /**
     * @var array
     */
    public static $allNamespaces = [];

    public static function check($checks)
    {
        $psr4 = ComposerJson::readAutoload();

        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            foreach ($files as $classFilePath) {
                $absFilePath = $classFilePath->getRealPath();

                $tokens = token_get_all(file_get_contents($absFilePath));

                foreach ($checks as $check) {
                    $check::check($tokens, $absFilePath, $classFilePath, $psr4Path, $psr4Namespace);
                }
            }
        }
    }

    public static function classList()
    {
        $psr4 = ComposerJson::readKey('autoload.psr-4');

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

                $filePath = str_replace(base_path(), '', $classFilePath->getRealPath());

                $composerPath = str_replace('/', '\\', $psr4Path);

                // replace composer base_path with composer namespace
                /**
                 *  "psr-4": {
                 *      "App\\": "app/"
                 *  }.
                 */
                $ns = str_replace(trim($composerPath, '\\'), trim($psr4Namespace, '\\/'), $filePath);
                $t = (str_replace('.php', '', [$ns, $fileName]));
                self::$allNamespaces[$t[1]][] = trim($t[0], '\\');
            }
        }

        return self::$allNamespaces;
    }
}
