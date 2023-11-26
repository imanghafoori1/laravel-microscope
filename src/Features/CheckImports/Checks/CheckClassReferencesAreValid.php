<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\ImportsAnalyzer;

class CheckClassReferencesAreValid
{
    public static $checkWrong = true;

    public static $checkUnused = true;

    public static $extraImportsHandler = Handlers\UnusedImports::class;

    public static $unusedWrongImportsHandler = Handlers\UnusedWrongImports::class;

    public static $wrongClassRefsHandler = Handlers\FixWrongClassRefs::class;

    public static function check($tokens, $absFilePath, $imports = [])
    {
        loopStart:
        [
            $hostNamespace,
            $extraWrongImports,
            $extraCorrectImports,
            $wrongClassRefs,
            $wrongDocblockRefs,
        ] = ImportsAnalyzer::getWrongRefs($tokens, $absFilePath, $imports);

        if (self::$checkWrong && self::$wrongClassRefsHandler) {
            [$tokens, $isFixed] = self::$wrongClassRefsHandler::handle(
                array_merge($wrongClassRefs, $wrongDocblockRefs),
                $absFilePath,
                $hostNamespace,
                $tokens
            );

            if ($isFixed) {
                goto loopStart;
            }
        }

        self::handleExtraImports($absFilePath, $extraWrongImports, $extraCorrectImports);

        return $tokens;
    }

    private static function handleExtraImports($absFilePath, $extraWrongImports, $extraCorrectImports)
    {
        // Extra wrong imports:
        if (self::$unusedWrongImportsHandler) {
            self::$unusedWrongImportsHandler::handle($extraWrongImports, $absFilePath);
        }

        // Extra correct imports:
        if (self::$checkUnused && self::$extraImportsHandler) {
            self::$extraImportsHandler::handle($extraCorrectImports, $absFilePath);
        }
    }
}
