<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Checks\CheckEarlyReturn;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Iterators\ClassMapIterator;
use JetBrains\PhpStorm\ExpectedValues;

class CheckEarlyReturns extends Command
{
    protected $signature = 'check:early_returns {--s|nofix} {--f|file=} {--d|folder=}';

    protected $description = 'Applies the early return on the classes';

    #[ExpectedValues(values: [0, 1])]
    public function handle()
    {
        ErrorPrinter::singleton($this->output);

        if ($this->option('nofix')) {
            $this->info(PHP_EOL.' Checking for possible code flattenings...'.PHP_EOL);
        }

        if (! $this->option('nofix') && ! $this->startWarning()) {
            return 0;
        }

        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');
        [$psr4Stats, $classMapStats] = self::applyCheckEarly($fileName, $folder, $this->option('nofix'));
        $this->getOutput()->writeln(implode(PHP_EOL, [
            Psr4Report::printAutoload($psr4Stats, $classMapStats),
        ]));

        return ErrorPrinter::singleton()->hasErrors() ? 1 : 0;
    }

    private function startWarning()
    {
        $this->info(PHP_EOL.' Checking for Early Returns...');
        $this->warn(' Warning: This command is going to make "CHANGES" to your files!');

        return $this->output->confirm(' Do you have committed everything in git?');
    }

    private static function getParams($nofix): array
    {
        return [
            'nofix' => $nofix,
            'nofixCallback' => function ($absPath) {
                $this->line('<fg=red>    - '.FilePath::getRelativePath($absPath).'</fg=red>');
            },
            'fixCallback' => function ($filePath, $tries) {
                $this->warn(PHP_EOL.$tries.' fixes applied to: '.class_basename($filePath));
            },
        ];
    }

    public static function applyCheckEarly(string $fileName, string $folder, $nofix): array
    {
        $check = [CheckEarlyReturn::class];
        $params = self::getParams($nofix);
        $psr4stats = ForPsr4LoadedClasses::check($check, $params, $fileName, $folder);
        $classMapStats = ClassMapIterator::iterate(base_path(), $check, $params, $fileName, $folder);

        return [$psr4stats, $classMapStats];
    }
}
