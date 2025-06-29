<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckClassyStrings;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Iterators\ClassMapIterator;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;

class ClassifyStrings extends Command
{
    protected $signature = 'check:stringy_classes
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $description = 'Replaces string references with ::class version of them.';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking stringy classes...');
        app()->singleton('current.command', function () {
            return $this;
        });
        $errorPrinter->printer = $this->output;

        $pathDTO = PathFilterDTO::makeFromOption($this);

        [$psr4Stats, $classMapStats] = self::classifyString($pathDTO);

        $this->getOutput()->writeln(implode(PHP_EOL, [
            Psr4Report::printAutoload($psr4Stats, $classMapStats),
        ]));

        $this->getOutput()->writeln(CheckStringyMsg::finished());

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    public static function classifyString(PathFilterDTO $pathFilterDTO): array
    {
        $psr4Stats = ForPsr4LoadedClasses::check([CheckStringy::class], [], $pathFilterDTO);
        $classMapStats = ClassMapIterator::iterate(base_path(), [CheckStringy::class], [], $pathFilterDTO);

        return [$psr4Stats, $classMapStats];
    }
}
