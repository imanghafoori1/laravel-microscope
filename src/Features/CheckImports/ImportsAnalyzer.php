<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use ErrorException;
use Imanghafoori\TokenAnalyzer\ClassReferenceFinder;
use Imanghafoori\TokenAnalyzer\ClassRefExpander;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;
use RuntimeException;

class ImportsAnalyzer
{
    public static $refCount = 0;

    public static $unusedImportsCount = 0;

    public static $wrongImportsCount = 0;

    public static $wrongClassRefCount = 0;

    public static $existenceChecker = ExistenceChecker::class;

    public static function getWrongRefs($tokens, $absFilePath, $imports): array
    {
        [
            $classReferences,
            $hostNamespace,
            $unusedImports,
            $docblockRefs,
        ] = self::findClassRefs($tokens, $absFilePath, $imports);

        [$wrongClassRefs] = self::filterWrongClassRefs($classReferences, $absFilePath);
        [$wrongDocblockRefs] = self::filterWrongClassRefs($docblockRefs, $absFilePath);
        [$extraWrongImports, $extraCorrectImports] = self::filterWrongClassRefs($unusedImports, $absFilePath);

        return [
            $hostNamespace,
            $extraWrongImports,
            $extraCorrectImports,
            $wrongClassRefs,
            $wrongDocblockRefs,
        ];
    }

    private static function filterWrongClassRefs($classReferences, $absFilePath): array
    {
        $wrongClassRefs = [];
        $correctClassRefs = [];

        foreach ($classReferences as $key => $classReference) {
            $class = $classReference['class'] ?? $classReference[0];

            if (self::$existenceChecker::check($class, $absFilePath)) {
                $correctClassRefs[$key] = $classReference;
            } else {
                $wrongClassRefs[$key] = $classReference;
            }
        }

        ImportsAnalyzer::$refCount += count($classReferences);
        ImportsAnalyzer::$wrongClassRefCount += count($wrongClassRefs);

        return [$wrongClassRefs, $correctClassRefs];
    }

    private static function findClassRefs($tokens, $absFilePath, $imports)
    {
        try {
            [$classes, $namespace] = ClassReferenceFinder::process($tokens);

            $docblockRefs = ClassReferenceFinder::readRefsInDocblocks($tokens);

            $unusedImports = ParseUseStatement::getUnusedImports($classes, $imports, $docblockRefs);

            [$classReferences, $hostNamespace] = ClassRefExpander::expendReferences($classes, $imports, $namespace);
            $docblockRefs = ClassReferenceFinder::getExpandedDocblockRefs($imports, $docblockRefs, $hostNamespace);

            return [$classReferences, $hostNamespace, $unusedImports, $docblockRefs];
        } catch (ErrorException $e) {
            self::requestIssue($absFilePath);

            return [[], '', [], []];
        } catch (RuntimeException $e) {
            self::requestIssue($absFilePath);

            return [[], '', [], []];
        }
    }

    private static function requestIssue(string $path)
    {
        dump('(O_o)   Well, It seems we had some problem parsing the contents of:   (o_O)');
        dump('Submit an issue on github: https://github.com/imanghafoori1/microscope');
        dump('Send us the contents of: '.$path);
    }
}
