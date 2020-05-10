<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Psr4Classes;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Checks\CheckStringy;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;
use Imanghafoori\LaravelMicroscope\Analyzers\ReplaceLine;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\CheckClasses;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class ClassifyStrings extends Command
{
    use LogsErrors;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:stringy_classes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replaces string references with ::class version of them.';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking strings...');

        app()->singleton('current.command', $this);

        $errorPrinter->printer = $this->output;

        /* $psr4 = ComposerJson::readKey('autoload.psr-4');

        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);

            foreach ($files as $file) {
                $absFilePath = $file->getRealPath();

                $tokens = token_get_all(file_get_contents($absFilePath));
                CheckStringy::check($tokens, $absFilePath);
            }
        }*/
        Psr4Classes::check([CheckStringy::class]);

        $this->finishCommand($errorPrinter);
    }
}
