<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\BladeFiles;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Traits\ScansFiles;
use Imanghafoori\LaravelMicroscope\Checks\CheckModelClass;
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
     * @throws \ErrorException
     * @return mixed
     */
    public function handle(ErrorPrinter $errorPrinter)
    {
        $t1 = microtime(true);
        $this->info('Checking to extract blade partials...');

        $errorPrinter->printer = $this->output;

        BladeFiles::check([ExtractBladePartial::class]);

        $this->finishCommand($errorPrinter);
        $this->info('Total elapsed time:'.(round(microtime(true) - $t1, 2)).' seconds');
    }


}
