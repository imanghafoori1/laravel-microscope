<?php

namespace Imanghafoori\LaravelMicroscope\Features\FacadeAlias;

use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class FacadeAliasReporter
{
    public static $errorCount = 0;

    /**
     * @var \Illuminate\Console\Command
     */
    public static $command;

    public static function handle(PhpFileDescriptor $file, $usageInfo, $base, $alias, $tokens)
    {
        $relativePath = $file->relativePath();

        $message = '   <fg=red>Facade alias</>: <fg=yellow>'.$base.'</> for <fg=yellow>'.$alias.'</>';
        $output = self::$command->getOutput();
        $output->writeln($message);
        $output->writeln('   at <fg=green>'.$relativePath.'</>:'.$usageInfo[1]);
        $output->writeln('   ');

        self::$errorCount++;
    }
}
