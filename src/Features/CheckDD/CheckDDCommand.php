<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDD;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class CheckDDCommand extends BaseCommand
{
    protected $signature = 'check:dd
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
';

    protected $description = 'Checks the debug functions.';

    public $checks = [CheckDD::class];

    public $initialMsg = 'Checking dd...';

    public $customMsg = '';

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        CheckDD::$onErrorCallback = function (PhpFileDescriptor $file, $token) {
            ErrorPrinter::singleton()->simplePendError(
                $token[1], $file->getAbsolutePath(), $token[2], 'ddFound', 'Debug function found: '
            );
        };

        $iterator->printAll([
            $iterator->forComposerLoadedFiles(),
            $iterator->forRoutes(),
            PHP_EOL.$iterator->forBladeFiles(),
        ]);
    }
}
