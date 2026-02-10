<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckGenericDocBlocks;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class GenericDocblocksHandler
{
    public static function handle(PhpFileDescriptor $file, $token)
    {
        ErrorPrinter::singleton()->simplePendError(
            $token[1] ?? '',
            $file,
            ($token[2] ?? 5) - 4,
            'generic_docs',
            'Docblock removed:'
        );
    }
}
