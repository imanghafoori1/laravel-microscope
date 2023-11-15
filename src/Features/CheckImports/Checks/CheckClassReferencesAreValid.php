<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\UnusedImports;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\UnusedWrongImports;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\WrongClassRefs;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\ImportsAnalyzer;

class CheckClassReferencesAreValid
{
    public static $checkWrong = true;

    public static $checkUnused = true;

    public static $unusedImportsHandler = UnusedImports::class;

    public static $unusedWrongImports = UnusedWrongImports::class;

    public static $wrongClassRefs = WrongClassRefs::class;

    public static function check($tokens, $absFilePath, $params = [])
    {
        event('laravel_microscope.checking_file', [$absFilePath]);

        return self::checkAndHandleClassRefs($tokens, $absFilePath, $params);
    }

    private static function checkAndHandleClassRefs($tokens, $absFilePath, $imports)
    {
        loopStart:
        [
            $hostNamespace,
            $unusedWrongImports,
            $unusedCorrectImports,
            $wrongClassRefs,
            $wrongDocblockRefs,
        ] = ImportsAnalyzer::getWrongRefs($tokens, $absFilePath, $imports);

        if (self::$checkWrong) {
            [$tokens, $isFixed] = self::$wrongClassRefs::handle(
                array_merge($wrongClassRefs, $wrongDocblockRefs),
                $absFilePath,
                $hostNamespace,
                $tokens
            );

            if ($isFixed) {
                goto loopStart;
            }

            self::$unusedWrongImports::handle($unusedWrongImports, $absFilePath);
        }

        if (self::$checkUnused) {
            self::$unusedImportsHandler::handle($unusedCorrectImports, $absFilePath);
        }

        return $tokens;
    }
}
