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

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:extract_blades';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks to extract blade partials';

    /**
     * Execute the console command.
     *
     * @param  ErrorPrinter  $errorPrinter
     *
     * @return mixed
     */
    public function handle(ErrorPrinter $errorPrinter)
    {
        if (! $this->startWarning()) {
            return ;
        }

        event('microscope.start.command');

        $errorPrinter->printer = $this->output;

        BladeFiles::check([ExtractBladePartial::class]);

        $this->info('Blade files extracted.');
        $this->printTime();
    }

    private function startWarning()
    {
        $this->info('Checking to extract blade partials...');
        $this->warn('This command is going to make changes to your files!');
        return $this->output->confirm('Do you have committed everything in git?', true);
    }

    private function printTime()
    {
        $this->info('Total elapsed time: '.round(microtime(true) - microscope_start, 2).' sec');
    }
}
