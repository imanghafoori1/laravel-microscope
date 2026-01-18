<?php

namespace Imanghafoori\LaravelMicroscope\Features;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckAll extends Command
{
    protected $signature = 'check:all {--d|detailed : Show files being checked} {--f|force} {--s|nofix : avoids the automatic fixes}';

    protected $description = 'Run all checks with one command';

    protected $commandType = 'checks';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $t1 = microtime(true);
        $errorPrinter->printer = $this->output;

        // Turns off error logging.
        $errorPrinter->logErrors = false;

        $this->call('check:psr4', ['--nofix' => $this->option('nofix'), '--force' => $this->option('force')]);
        $this->call('check:imports', ['--nofix' => $this->option('nofix')]);
        $this->call('check:gates');
        $this->call('check:views');
        $this->call('check:routes');
        $this->call('check:stringy_classes');
        $this->call('check:dd');
        $this->call('check:dead_controllers');
        $this->call('check:bad_practices');

        // turns on error logging.
        $errorPrinter->logErrors = true;

        $duration = microtime(true) - $t1;
        $errorPrinter->printer->writeln(self::getTimeMsg($duration), 2);

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private static function getTimeMsg($time): string
    {
        return 'time: '.round($time, 2).' (sec)';
    }
}
