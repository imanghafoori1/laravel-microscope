<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\ImportsAnalyzer;

class CheckClassReferencesAreValid
{
    public static $checkWrong = true;

    public static $checkUnused = true;

    public static $unusedImportsHandler = Handlers\UnusedImports::class;

    public static $unusedWrongImports = Handlers\UnusedWrongImports::class;

    public static $wrongClassRefs = Handlers\FixWrongClassRefs::class;

    public static function check($tokens, $absFilePath, $imports = [])
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

        self::handleExtraImports($absFilePath, $unusedWrongImports, $unusedCorrectImports);

        return $tokens;
    }

    private static function handleExtraImports($absFilePath, $unusedWrongImports, $unusedCorrectImports)
    {
        // Extra wrong imports:
        if (self::$unusedWrongImports) {
            self::$unusedWrongImports::handle($unusedWrongImports, $absFilePath);
        }

        // Extra correct imports:
        if (self::$checkUnused && self::$unusedImportsHandler) {
            self::$unusedImportsHandler::handle($unusedCorrectImports, $absFilePath);
        }
    }
}
