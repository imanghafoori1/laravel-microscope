<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\FixWrongClassRefs;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\UnusedImports;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\UnusedWrongImports;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\ImportsAnalyzer;

class CheckClassReferencesAreValid
{
    public static $checkWrong = true;

    public static $checkUnused = true;

    public static $unusedImportsHandler = UnusedImports::class;

    public static $unusedWrongImports = UnusedWrongImports::class;

    public static $wrongClassRefs = FixWrongClassRefs::class;

    public static function check($tokens, $absFilePath, $params = [])
    {
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

        if (self::$checkWrong && self::$wrongClassRefs) {
            [$tokens, $isFixed] = self::$wrongClassRefs::handle(
                array_merge($wrongClassRefs, $wrongDocblockRefs),
                $absFilePath,
                $hostNamespace,
                $tokens
            );

            if ($isFixed) {
                goto loopStart;
            }
        }

        // Extra wrong imports:
        if (self::$unusedWrongImports) {
            self::$unusedWrongImports::handle($unusedWrongImports, $absFilePath);
        }

        // Extra correct imports:
        if (self::$checkUnused && self::$unusedImportsHandler) {
            self::$unusedImportsHandler::handle($unusedCorrectImports, $absFilePath);
        }

        return $tokens;
    }
}
