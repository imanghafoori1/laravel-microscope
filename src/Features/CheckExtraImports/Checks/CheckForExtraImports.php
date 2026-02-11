<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Checks;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Features\CheckExtraImports\Handlers;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Cache;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Foundations\UseStatementParser;
use Imanghafoori\TokenAnalyzer\ClassReferenceFinder;
use Imanghafoori\TokenAnalyzer\DocblockReader;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;
use RuntimeException;

class CheckForExtraImports implements Check
{
    public static $imports;

    public static $importsCount = 0;

    public static function setImports()
    {
        self::$imports = UseStatementParser::get();
    }

    public static function check(PhpFileDescriptor $file)
    {
        $imports = self::$imports;
        $uses = Cache::getForever($file->getMd5(), 'check_extra_imports', function () use ($file, $imports) {
            $uses = $imports($file);

            return [
                'extraImports' => self::findClassRefs($file->getTokens(), $uses),
                'count' => count($uses[array_key_first($uses)]),
            ];
        });

        Handlers\ExtraImportsHandler::handle($uses['extraImports'], $file);
        self::$importsCount += $uses['count'];
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
