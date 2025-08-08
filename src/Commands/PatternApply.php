<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Generator;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use Imanghafoori\LaravelMicroscope\Iterators\BladeFiles\CheckBladePaths;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedClassMaps;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\LaravelMicroscope\SearchReplace\PatternRefactorings;
use Imanghafoori\SearchReplace\PatternParser;

trait PatternApply
{
    abstract public function getPatterns();

    private function patternCommand(ErrorPrinter $errorPrinter): int
    {
        $pathDTO = PathFilterDTO::makeFromOption($this);

        $errorPrinter->printer = $this->output;

        Reporters\Psr4Report::$callback = function () use ($errorPrinter) {
            $errorPrinter->flushErrors();
        };

        $patterns = $this->getPatterns();

        $report = $this->appliesPatterns($patterns, $pathDTO);

        $this->getOutput()->writeln($report);

        $this->finishCommand($errorPrinter);

        $errorPrinter->printTime();

        return $this->hasFoundPatterns() ? 1 : 0;
    }

    /**
     * @return string
     */
    private function appliesPatterns(array $patterns, PathFilterDTO $pathDTO)
    {
        $parsedPatterns = PatternParser::parsePatterns($patterns);

        $paramProvider = function () use ($parsedPatterns) {
            return [$parsedPatterns];
        };

        $check = [PatternRefactorings::class];

        $psr4Stats = ForPsr4LoadedClasses::check($check, $paramProvider, $pathDTO);
        $classMapStats = ForAutoloadedClassMaps::check(base_path(), $check, [$parsedPatterns], $pathDTO);
        CheckBladePaths::$readOnly = false;
        $bladeStats = ForBladeFiles::check($check, [$parsedPatterns], $pathDTO);

        return $this->getFinalMessage($psr4Stats, $classMapStats, $bladeStats);
    }

    private function getFinalMessage(array $psr4Stats, array $classMapStats, Generator $bladeStats): string
    {
        try {
            Psr4Report::formatAndPrintAutoload($psr4Stats, $classMapStats, $this->getOutput());

            return implode(PHP_EOL, [
                Reporters\BladeReport::getBladeStats($bladeStats),
            ]);
        } finally {
            CachedFiles::writeCacheFiles();
        }
    }

    private function hasFoundPatterns(): bool
    {
        return PatternRefactorings::$patternFound;
    }
}
