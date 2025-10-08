<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks;

use Closure;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class CheckClassReferencesAreValid implements Check
{
    public static $checkWrong = true;

    public static $checkExtra = true;

    public static $cache = [];

    public static $extraCorrectImportsHandler = Handlers\ExtraCorrectImports::class;

    public static $extraWrongImportsHandler = Handlers\ExtraWrongImports::class;

    public static $wrongClassRefsHandler = Handlers\FixWrongClassRefs::class;

    public static $imports;

    public static function check(PhpFileDescriptor $file)
    {
        $imports = self::$imports;

        loopStart:

        $refFinder = function () use ($file, $imports) {
            $tokens = $file->getTokens();
            $imports = $imports($file);
            $absFilePath = $file->getAbsolutePath();

            return ImportsAnalyzer::findClassRefs($tokens, $absFilePath, $imports);
        };

        [
            $classReferences,
            $hostNamespace,
            $extraImports,
            $docblockRefs,
            $attributeReferences,
        ] = self::getForever($file->getMd5(), $refFinder);

        $absFilePath = $file->getAbsolutePath();
        [$wrongClassRefs] = ImportsAnalyzer::filterWrongClassRefs($classReferences, $absFilePath);
        [$wrongDocblockRefs] = ImportsAnalyzer::filterWrongClassRefs($docblockRefs, $absFilePath);
        [$extraWrongImports, $extraCorrectImports] = ImportsAnalyzer::filterWrongClassRefs($extraImports, $absFilePath);

        $wrongClassRefs = array_merge($wrongClassRefs, $wrongDocblockRefs);
        $tokens = null;
        if ($wrongClassRefs && self::$checkWrong && self::$wrongClassRefsHandler) {
            [$tokens, $isFixed] = self::$wrongClassRefsHandler::handle(
                $wrongClassRefs,
                $file,
                $hostNamespace,
                $file->getTokens(),
            );

            if ($isFixed) {
                goto loopStart;
            }
        }

        self::handleExtraImports($file, $extraWrongImports, $extraCorrectImports);

        return $tokens;
    }

    private static function handleExtraImports($file, $extraWrongImports, $extraCorrectImports)
    {
        // Extra wrong imports:
        if (self::$extraWrongImportsHandler) {
            self::$extraWrongImportsHandler::handle($extraWrongImports, $file);
        }

        // Extra correct imports:
        if (self::$checkExtra && self::$extraCorrectImportsHandler) {
            self::$extraCorrectImportsHandler::handle($extraCorrectImports, $file);
        }
    }

    public static function getForever($md5, Closure $refFinder)
    {
        return self::$cache[$md5] ?? (self::$cache[$md5] = $refFinder());
    }
}
