<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Iterators\BladeFiles;
use Imanghafoori\LaravelMicroscope\Iterators\BladeFiles\CheckBladePaths;
use Imanghafoori\LaravelMicroscope\Iterators\ClassMapIterator;
use Imanghafoori\LaravelMicroscope\SearchReplace\PatternRefactorings;
use Imanghafoori\SearchReplace\PatternParser;

trait PatternApply
{
    abstract public function getPatterns();

    private function patternCommand(ErrorPrinter $errorPrinter): int
    {
        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');

        $errorPrinter->printer = $this->output;

        Reporters\Psr4Report::$callback = function () use ($errorPrinter) {
            $errorPrinter->flushErrors();
        };

        $patterns = $this->getPatterns();
        $this->appliesPatterns($patterns, $fileName, $folder);

        $this->finishCommand($errorPrinter);

        $errorPrinter->printTime();

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private function appliesPatterns(array $patterns, string $fileName, string $folder): void
    {
        $parsedPatterns = PatternParser::parsePatterns($patterns);

        $paramProvider = function () use ($parsedPatterns) {
            return [$parsedPatterns];
        };

        $check = [PatternRefactorings::class];

        $psr4Stats = ForPsr4LoadedClasses::check($check, $paramProvider, $fileName, $folder);
        $classMapStats = ClassMapIterator::iterate(base_path(), $check, [$parsedPatterns], $fileName, $folder);
        CheckBladePaths::$readOnly = false;
        $bladeStats = BladeFiles::check($check, [$parsedPatterns], $fileName, $folder);

        $this->getOutput()->writeln(implode(PHP_EOL, [
            Reporters\Psr4Report::printAutoload($psr4Stats, $classMapStats),
            Reporters\BladeReport::getBladeStats($bladeStats),
        ]));
    }
}
