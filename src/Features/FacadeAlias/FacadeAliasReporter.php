<?php

namespace Imanghafoori\LaravelMicroscope\Features\FacadeAlias;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class FacadeAliasReporter
{
    public static $errorCount = 0;

    public static function handle(PhpFileDescriptor $file, $usageInfo, $base, $alias)
    {
        $message = Color::red($base).' for '.Color::yellow($alias);

        ErrorPrinter::singleton()->simplePendError($message, $file, $usageInfo[1], 'facade_alias', 'Alias found:');

        self::$errorCount++;
    }
}
