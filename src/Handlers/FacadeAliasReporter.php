<?php

namespace Imanghafoori\LaravelMicroscope\Handlers;

use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;

class FacadeAliasReporter
{
    public static $command;

    public static function handle($absFilePath, $usageInfo, $base, $alias, $tokens)
    {
        self::report($absFilePath, $usageInfo, $base, $alias);
    }

    private static function report($absFilePath, $use, $base, $aliases)
    {
        $relativePath = FilePath::normalize(\trim(\str_replace(base_path(), '', $absFilePath), '\\/'));

        $message = '   <fg=red>Facade alias</>: <fg=yellow>'.$base.'</> for <fg=yellow>'.$aliases.'</>';
        self::$command->getOutput()->writeln($message);
        self::$command->getOutput()->writeln('   at <fg=green>'.$relativePath.'</>:'.$use[1]);
    }
}
