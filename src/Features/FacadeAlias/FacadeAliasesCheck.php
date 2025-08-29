<?php

namespace Imanghafoori\LaravelMicroscope\Features\FacadeAlias;

use Illuminate\Foundation\AliasLoader;
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

    public static function check(PhpFileDescriptor $file)
    {
        if (CachedFiles::isCheckedBefore('check_facade_aliad_command', $file)) {
            return;
        }
        $tokens = $file->getTokens();
        $absFilePath = $file->getAbsolutePath();

        $aliases = AliasLoader::getInstance()->getAliases();
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

                $tokens = self::$handler::handle($absFilePath, $usageInfo, $base, $expandedAlias, $tokens, $import);
            }
        }

        if ($hasError === false) {
            CachedFiles::put('check_facade_aliad_command', $file);
        }

        return $tokens;
    }
}
