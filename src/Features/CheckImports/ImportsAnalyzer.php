<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use ErrorException;
use Imanghafoori\TokenAnalyzer\ClassReferenceFinder;
use Imanghafoori\TokenAnalyzer\ClassRefExpander;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;
use RuntimeException;
use Throwable;

class ImportsAnalyzer
{
    public static $refCount = 0;

    public static $unusedImportsCount = 0;

    public static $wrongImportsCount = 0;

    public static $wrongClassRefCount = 0;

    public static function getWrongRefs($tokens, $absFilePath, $imports): array
    {
        [$classReferences, $hostNamespace, $unusedImports, $docblockRefs] = self::findClassRefs($tokens, $absFilePath, $imports);

        [$wrongClassRefs] = self::filterWrongClassRefs($classReferences, $absFilePath);
        [$wrongDocblockRefs] = self::filterWrongClassRefs($docblockRefs, $absFilePath);
        [$unusedWrongImports, $unusedCorrectImports] = self::filterWrongClassRefs($unusedImports, $absFilePath);

        return [
            $hostNamespace,
            $unusedWrongImports,
            $unusedCorrectImports,
            $wrongClassRefs,
            $wrongDocblockRefs,
        ];
    }

    private static function filterWrongClassRefs($classReferences, $absFilePath): array
    {
        $wrongClassRefs = [];
        $correctClassRefs = [];
        foreach ($classReferences as $y => $classReference) {
            ImportsAnalyzer::$refCount++;
            $class = $classReference['class'] ?? $classReference[0];

            if (self::exists($class, $absFilePath)) {
                $correctClassRefs[$y] = $classReference;
            } else {
                ImportsAnalyzer::$wrongClassRefCount++;
                $wrongClassRefs[$y] = $classReference;
            }
        }

        return [$wrongClassRefs, $correctClassRefs];
    }

    private static function findClassRefs($tokens, $absFilePath, $imports)
    {
        try {
            [$classes, $namespace] = ClassReferenceFinder::process($tokens);

            $docblockRefs = ClassReferenceFinder::readRefsInDocblocks($tokens);

            $unusedImports = ParseUseStatement::getUnusedImports($classes, $imports, $docblockRefs);

            [$classReferences, $hostNamespace] = ClassRefExpander::expendReferences($classes, $imports, $namespace);

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

    private static function exists($class, $absFilePath): bool
    {
        if (! self::isAbsent($class) || \function_exists($class)) {
            return true;
        }

        try {
            require_once $absFilePath;
        } catch (Throwable $e) {
            return false;
        }

        if (! self::isAbsent($class) || \function_exists($class)) {
            return true;
        }

        return false;
    }

    public static function isAbsent($class)
    {
        return ! class_exists($class) && ! interface_exists($class) && ! trait_exists($class) && ! (function_exists('enum_exists') && enum_exists($class));
    }
}
