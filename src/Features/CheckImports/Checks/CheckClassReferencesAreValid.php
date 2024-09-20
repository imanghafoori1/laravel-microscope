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

    public static function check(PhpFileDescriptor $file, $imports = [])
    {
        loopStart:
        $md5 = $file->getMd5();
        $absFilePath = $file->getAbsolutePath();

        $tokens = $file->getTokens();
        $refFinder = function () use ($file, $tokens, $imports) {
            $absFilePath = $file->getAbsolutePath();

            return ImportsAnalyzer::findClassRefs($tokens, $absFilePath, $imports);
        };

        if (count($tokens) > 100) {
            $refFinder = function () use ($md5, $refFinder) {
                return self::getForever($md5, $refFinder);
            };
        }

        [
            $classReferences,
            $hostNamespace,
            $extraImports,
            $docblockRefs,
            $attributeReferences,
        ] = $refFinder();

        [$wrongClassRefs] = ImportsAnalyzer::filterWrongClassRefs($classReferences, $absFilePath);
        [$wrongDocblockRefs] = ImportsAnalyzer::filterWrongClassRefs($docblockRefs, $absFilePath);
        [$extraWrongImports, $extraCorrectImports] = ImportsAnalyzer::filterWrongClassRefs($extraImports, $absFilePath);

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

    public static function getForever($md5, Closure $refFinder)
    {
        return self::$cache[$md5] ?? (self::$cache[$md5] = $refFinder());
    }
}
