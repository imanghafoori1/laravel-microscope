<?php

namespace Imanghafoori\LaravelMicroscope\Features\FacadeAlias;

use Generator;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Features\SearchReplace\CachedFiles;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Foundations\UseStatementParser;

class FacadeAliasesCheck implements Check
{
    public static $alias = '-all-';

    /**
     * @var class-string
     */
    public static $handler = FacadeAliasReplacer::class;

    public static $importsProvider = UseStatementParser::class;

    /**
     * @var array
     */
    public static $aliases = [];

    public static function check(PhpFileDescriptor $file)
    {
        if (CachedFiles::isCheckedBefore('check_facade_alias_command', $file)) {
            return;
        }

        foreach (self::findAliases($file) as $data) {
            self::$handler::handle($file, ...$data);
        }

        // if there are no errors:
        if (isset($data) === false) {
            CachedFiles::put('check_facade_alias_command', $file);
        }
    }

    private static function findAliases(PhpFileDescriptor $file): Generator
    {
        $aliases = self::$aliases;

        foreach (self::$importsProvider::parse($file) as $import) {
            foreach ($import as $base => $usageInfo) {
                $shortAlias = $usageInfo[0];
                if (! isset($aliases[$shortAlias])) {
                    continue;
                }
                if (self::$alias !== '-all-' && ! in_array(strtolower($shortAlias), self::$alias)) {
                    continue;
                }
                $expandedAlias = $aliases[$shortAlias];

                yield [$usageInfo, $base, $expandedAlias, $import];
            }
        }
    }
}
