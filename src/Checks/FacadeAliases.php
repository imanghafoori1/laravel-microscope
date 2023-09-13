<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Foundation\AliasLoader;
use Imanghafoori\LaravelMicroscope\Handlers\FacadeAliasReplacer;

class FacadeAliases
{
    public static $handler = FacadeAliasReplacer::class;

    /**
     * @var \Imanghafoori\LaravelMicroscope\Commands\CheckImports
     */
    public static $command;

    public static function check($tokens, $absFilePath, $imports)
    {
        $aliases = AliasLoader::getInstance()->getAliases();
        self::$handler::$command = self::$command;

        foreach ($imports as $import) {
            foreach ($import as $base => $usageInfo) {
                if (! isset($aliases[$usageInfo[0]])) {
                    continue;
                }
                $alias = $aliases[$usageInfo[0]];

                $tokens = self::$handler::handle($absFilePath, $usageInfo, $base, $alias, $tokens);
            }
        }

        return $tokens;
    }
}
