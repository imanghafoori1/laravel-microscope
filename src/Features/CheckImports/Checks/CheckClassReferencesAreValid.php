<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Cache;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Foundations\UseStatementParser;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class CheckClassReferencesAreValid implements Check
{
    public static $checkWrong = true;

    public static $extraWrongImportsHandler = Handlers\ExtraWrongImportsHandler::class;

    public static $wrongClassRefsHandler = Handlers\FixWrongClassRefs::class;

    public static $importsProvider = UseStatementParser::class;

    public static function check(PhpFileDescriptor $file)
    {
        loopStart:

        $refFinder = fn () => ImportsAnalyzer::findClassRefs(
            $file->getTokens(),
            $file->getAbsolutePath(),
            self::$importsProvider::parse($file)
        );

        [
            $classReferences,
            $hostNamespace,
            $extraImports,
            $docblockRefs,
            $attributeReferences,
        ] = Cache::getForever($file->getMd5(), 'check_imports', $refFinder);

        $absFilePath = $file->getAbsolutePath();
        [$wrongClassRefs] = ImportsAnalyzer::filterWrongClassRefs($classReferences, $absFilePath);
        [$wrongDocblockRefs] = ImportsAnalyzer::filterWrongClassRefs($docblockRefs, $absFilePath);
        [$extraWrongImports] = ImportsAnalyzer::filterWrongClassRefs($extraImports, $absFilePath);

        $wrongClassRefs = array_merge($wrongClassRefs, $wrongDocblockRefs);

        if ($wrongClassRefs && self::$checkWrong && self::$wrongClassRefsHandler) {
            $isFixed = self::$wrongClassRefsHandler::handle(
                $wrongClassRefs,
                $file,
                $hostNamespace,
            );

            if ($isFixed) {
                goto loopStart;
            }
        }

        // Extra wrong imports:
        $handler = self::$extraWrongImportsHandler;
        $handler && $handler::handle($extraWrongImports, $file);
    }
}
