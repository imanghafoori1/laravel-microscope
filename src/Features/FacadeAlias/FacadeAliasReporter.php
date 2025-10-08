<?php

namespace Imanghafoori\LaravelMicroscope\Features\FacadeAlias;

use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;

class FacadeAliasReporter
{
    public static $errorCount = 0;

    /**
     * @var \Illuminate\Console\Command
     */
    public static $command;

    public static function handle($absFilePath, $usageInfo, $base, $alias, $tokens)
    {
        self::report($absFilePath, $usageInfo, $base, $alias);
    }

    private static function report($absFilePath, $use, $base, $aliases)
    {
        $relativePath = FilePath::normalize(FilePath::getRelativePath($absFilePath));

        $message = '   <fg=red>Facade alias</>: <fg=yellow>'.$base.'</> for <fg=yellow>'.$aliases.'</>';
        $output = self::$command->getOutput();
        $output->writeln($message);
        $output->writeln('   at <fg=green>'.$relativePath.'</>:'.$use[1]);
        $output->writeln('   ');

        self::$errorCount++;
    }
}
