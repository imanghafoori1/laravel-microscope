<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Traits\ScansFiles;
use Imanghafoori\LaravelMicroscope\Checks\ExtractBladePartial;
use Imanghafoori\LaravelMicroscope\Contracts\FileCheckContract;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckExtractBladeIncludes extends Command implements FileCheckContract
{
    use LogsErrors;

    use ScansFiles;

    protected $signature = 'check:extract_blades';

    protected $description = 'Checks to extract blade partials';

    public function handle(ErrorPrinter $errorPrinter)
    {
        if (! $this->startWarning()) {
            return ;
        }

        event('microscope.start.command');

        $errorPrinter->printer = $this->output;

        BladeFiles::check([ExtractBladePartial::class]);

        $this->info('Blade files extracted.');
    }

    private function startWarning()
    {
        $this->info('Checking to extract blade partials...');
        $this->warn('This command is going to make changes to your files!');
        return $this->output->confirm('Do you have committed everything in git?', true);
    }
}
