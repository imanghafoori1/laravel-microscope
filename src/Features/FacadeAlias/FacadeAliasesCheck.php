<?php

namespace Imanghafoori\LaravelMicroscope\Features\FacadeAlias;

use Illuminate\Foundation\AliasLoader;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class FacadeAliasesCheck implements Check
{
    /**
     * @var class-string
     */
    public static $handler = FacadeAliasReplacer::class;

    /**
     * @var \Illuminate\Console\OutputStyle
     */
    public static $command;

    public static function check(PhpFileDescriptor $file, $imports)
    {
        $tokens = $file->getTokens();
        $absFilePath = $file->getAbsolutePath();

        $aliases = AliasLoader::getInstance()->getAliases();
        self::$handler::$command = self::$command;

        foreach ($imports as $import) {
            foreach ($import as $base => $usageInfo) {
                if (! isset($aliases[$usageInfo[0]])) {
                    continue;
                }
                $alias = $aliases[$usageInfo[0]];

                $tokens = self::$handler::handle($absFilePath, $usageInfo, $base, $alias, $tokens, $import);
            }
        }

        return $tokens;
    }
}
