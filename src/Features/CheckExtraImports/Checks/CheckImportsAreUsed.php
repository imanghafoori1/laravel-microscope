<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Checks;

use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use RuntimeException;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Handlers;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\ClassReferenceFinder;
use Imanghafoori\TokenAnalyzer\DocblockReader;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class CheckImportsAreUsed implements Check
{
    use CachedCheck;

    private static $cacheKey = 'check_extra_imports';

    public static $imports;

    public static $importsCount = 0;

    public static function setImports()
    {
        CheckImportsAreUsed::$imports = function (PhpFileDescriptor $file) {
            $imports = ParseUseStatement::parseUseStatements($file->getTokens());

            return $imports[0] ?: [$imports[1]];
        };
    }

    public static function performCheck(PhpFileDescriptor $file)
    {
        $imports = self::$imports;
        $extraImports = self::findClassRefs($file->getTokens(), $imports($file));
        Handlers\ExtraImports::handle($extraImports, $file);
        self::$importsCount += count($extraImports);

        return count($extraImports) !== 0;
    }

    public static function findClassRefs($tokens, $imports)
    {
        try {
            [$classes, $namespace, $attributeRefs] = ClassReferenceFinder::process($tokens);

            $docblockRefs = DocblockReader::readRefsInDocblocks($tokens);

            return ParseUseStatement::getUnusedImports(
                array_merge($classes, $attributeRefs),
                $imports,
                $docblockRefs
            );
        } catch (RuntimeException $e) {
            return [[], '', [], []];
        }
    }
}
