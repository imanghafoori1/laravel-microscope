<?php

namespace Imanghafoori\LaravelMicroscope\Features\FacadeAlias;

use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\Console;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class FacadeAliasReporter
{
    public static $errorCount = 0;

    public static function handle(PhpFileDescriptor $file, $usageInfo, $base, $alias, $tokens)
    {
        $relativePath = $file->relativePath();

        $message = '   '.Color::red('Facade alias').': '.Color::yellow($base).' for '.Color::yellow($alias);

        $output = Console::getInstance();
        $output->writeln($message);
        $output->writeln('   at '.Color::green($relativePath).':'.$usageInfo[1]);
        $output->writeln('   ');

        self::$errorCount++;
    }
}
