<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\LaravelMicroscope\CheckClasses;
use Imanghafoori\LaravelMicroscope\Checks\CheckClassReferences;
use Imanghafoori\LaravelMicroscope\CheckViewRoute;
use Imanghafoori\LaravelMicroscope\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Util;

class CheckImports extends Command
{
    use LogsErrors;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the validity of use statements';

    /**
     * Execute the console command.
     *
     * @param  ErrorPrinter  $errorPrinter
     *
     * @return mixed
     */
    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking imports ...');

        $errorPrinter->printer = $this->output;

        $psr4 = Util::parseComposerJson('autoload.psr-4');

        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = CheckClasses::getAllPhpFiles($psr4Path);
            CheckClasses::checkImports($files, base_path(), $psr4Path, $psr4Namespace);
        }

        $methods = [
            [new CheckClassReferences, 'check'],
        ];
        (new CheckViewRoute)->check($methods);

        $this->checkConfig();

        $this->finishCommand($errorPrinter);
    }

    protected function checkConfig()
    {
        $user = config('auth.providers.users.model');
        if (! $user || ! class_exists($user) || ! is_subclass_of($user, Model::class)) {
            resolve(ErrorPrinter::class)->authConf();
        }
    }
}
