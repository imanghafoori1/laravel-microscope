<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class CheckClassReferencesAreValid
{
    public static $checkWrong = true;

    public static $checkExtra = true;

    public static $extraCorrectImportsHandler = Handlers\ExtraCorrectImports::class;

    public static $extraWrongImportsHandler = Handlers\ExtraWrongImports::class;

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
        if (self::$extraWrongImportsHandler) {
            self::$extraWrongImportsHandler::handle($extraWrongImports, $absFilePath);
        }

        // Extra correct imports:
        if (self::$checkExtra && self::$extraCorrectImportsHandler) {
            self::$extraCorrectImportsHandler::handle($extraCorrectImports, $absFilePath);
        }
    }
}
