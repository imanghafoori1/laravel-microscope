<?php

namespace Imanghafoori\LaravelMicroscope;

use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;

class Psr4Classes
{
    public static function check($checks)
    {
        $psr4 = ComposerJson::readKey('autoload.psr-4');

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

}
