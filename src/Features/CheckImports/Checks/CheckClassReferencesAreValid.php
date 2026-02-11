<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Cache;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class CheckClassReferencesAreValid implements Check
{
    public static $checkWrong = true;

    public static $extraWrongImportsHandler = Handlers\ExtraWrongImportsHandler::class;

    public static $wrongClassRefsHandler = Handlers\FixWrongClassRefs::class;

    public static $importsProvider;

    public static function check(PhpFileDescriptor $file)
    {
        $imports = self::$importsProvider;

        loopStart:

        $refFinder = fn () => ImportsAnalyzer::findClassRefs(
            $file->getTokens(),
            $file->getAbsolutePath(),
            $imports($file)
        );

        [
            $classReferences,
            $hostNamespace,
            $extraImports,
            $docblockRefs,
            $attributeReferences,
        ] = Cache::getForever($file->getMd5(), $refFinder);

        $absFilePath = $file->getAbsolutePath();
        [$wrongClassRefs] = ImportsAnalyzer::filterWrongClassRefs($classReferences, $absFilePath);
        [$wrongDocblockRefs] = ImportsAnalyzer::filterWrongClassRefs($docblockRefs, $absFilePath);
        [$extraWrongImports] = ImportsAnalyzer::filterWrongClassRefs($extraImports, $absFilePath);

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

        // Extra wrong imports:
        $handler = self::$extraWrongImportsHandler;
        $handler && $handler::handle($extraWrongImports, $file);

        return $tokens;
    }
}
