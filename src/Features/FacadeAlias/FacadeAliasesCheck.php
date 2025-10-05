<?php

namespace Imanghafoori\LaravelMicroscope\Features\FacadeAlias;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;

class FacadeAliasesCheck implements Check
{
    public static $alias = '-all-';

    /**
     * @var class-string
     */
    public static $handler = FacadeAliasReplacer::class;

    /**
     * @var \Illuminate\Console\OutputStyle
     */
    public static $command;

    public static $importsProvider;

    /**
     * @var array
     */
    public static $aliases = [];

    public static function check(PhpFileDescriptor $file)
    {
        if (CachedFiles::isCheckedBefore('check_facade_alias_command', $file)) {
            return;
        }

        [$tokens, $hasError] = self::performCheck($file);

        if ($hasError === false) {
            CachedFiles::put('check_facade_alias_command', $file);
        }

        return $tokens;
    }

    public static function performCheck(PhpFileDescriptor $file): array
    {
        $tokens = $file->getTokens();

        $aliases = self::$aliases;
        self::$handler::$command = self::$command;

        $imports = (self::$importsProvider)($file);
        $hasError = false;
        foreach ($imports as $import) {
            foreach ($import as $base => $usageInfo) {
                $shortAlias = $usageInfo[0];
                if (! isset($aliases[$shortAlias])) {
                    continue;
                }
                $hasError = true;
                if (self::$alias !== '-all-' && ! in_array(strtolower($shortAlias), self::$alias)) {
                    continue;
                }
                $expandedAlias = $aliases[$shortAlias];

                $tokens = self::$handler::handle($file, $usageInfo, $base, $expandedAlias, $tokens, $import);
            }
        }

        return [$tokens, $hasError];
    }
}
