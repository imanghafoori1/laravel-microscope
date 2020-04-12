<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\LaravelMicroscope\CheckClasses;
use Imanghafoori\LaravelMicroscope\Checks\CheckClassReferences;
use Imanghafoori\LaravelMicroscope\CheckViewRoute;
use Imanghafoori\LaravelMicroscope\Contracts\FileCheckContract;
use Imanghafoori\LaravelMicroscope\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Traits\ScansFiles;
use Imanghafoori\LaravelMicroscope\Util;

class CheckImports extends Command implements FileCheckContract
{
    use LogsErrors;
    use ScansFiles;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:imports {--d|detailed : Show files being checked}';

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
            try {
                $files = CheckClasses::getAllPhpFiles($psr4Path);
                CheckClasses::checkImports($files, $this);
            } catch (\ErrorException $e) {
                if (! Str::endsWith($e->getFile(), 'vendor\composer\ClassLoader.php')) {
                    throw $e;
                }

                $this->warnDumping($e->getMessage());
            }
        }

        (new CheckViewRoute)->check([
            [new CheckClassReferences, 'check'],
        ]);

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

    protected function warnDumping($msg)
    {
        dump('It seems composer has some trouble with autoload...');
        dump($msg);
        dump('Running "composer dump-autoload" command...');
        resolve(Composer::class)->dumpAutoloads();
    }
}
