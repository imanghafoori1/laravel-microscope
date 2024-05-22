<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckClassyStrings;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Iterators\ClassMapIterator;

class ClassifyStrings extends Command
{
    protected $signature = 'check:stringy_classes {--f|file=} {--d|folder=}';

    protected $description = 'Replaces string references with ::class version of them.';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking stringy classes...');
        app()->singleton('current.command', function () {
            return $this;
        });
        $errorPrinter->printer = $this->output;

        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');

        [$psr4Stats, $classMapStats] = self::classifyString($fileName, $folder);

        $this->getOutput()->writeln(implode(PHP_EOL, [
            Psr4Report::printAutoload($psr4Stats, $classMapStats),
        ]));

        $this->getOutput()->writeln(CheckStringyMsg::finished());

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    public static function classifyString(string $fileName, string $folder): array
    {
        $psr4Stats = ForPsr4LoadedClasses::check([CheckStringy::class], [], $fileName, $folder);
        $classMapStats = ClassMapIterator::iterate(base_path(), [CheckStringy::class], [], $fileName, $folder);

        return [$psr4Stats, $classMapStats];
    }
}
