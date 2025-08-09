<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Psr4ReportPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedFiles;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
use Imanghafoori\LaravelMicroscope\Iterators\BladeFiles\CheckBladePaths;
use Imanghafoori\LaravelMicroscope\Iterators\ForAutoloadedClassMaps;
use Imanghafoori\LaravelMicroscope\Iterators\ForRouteFiles;
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

        $this->appliesPatterns($patterns, $pathDTO);

        $this->finishCommand($errorPrinter);

        $errorPrinter->printTime();

        return $this->hasFoundPatterns() ? 1 : 0;
    }

    /**
     * @return void
     */
    private function appliesPatterns(array $patterns, PathFilterDTO $pathDTO)
    {
        $parsedPatterns = PatternParser::parsePatterns($patterns);

        $check = [PatternRefactorings::class];

        $routeFiles = ForRouteFiles::check($check, [$parsedPatterns], $pathDTO);
        $psr4Stats = ForPsr4LoadedClasses::check($check, [$parsedPatterns], $pathDTO);
        $classMapStats = ForAutoloadedClassMaps::check(base_path(), $check, [$parsedPatterns], $pathDTO);
        $autoloadedFilesStats = ForAutoloadedFiles::check(base_path(), $check, [$parsedPatterns], $pathDTO);
        CheckBladePaths::$readOnly = false;
        $bladeStats = ForBladeFiles::check($check, [$parsedPatterns], $pathDTO);

        $messages = self::getConsoleMessages($psr4Stats, $classMapStats, $autoloadedFilesStats, $bladeStats);
        $messages[] = CheckImportReporter::getRouteStats($routeFiles);
        try {
            Psr4ReportPrinter::printAll($messages, $this->getOutput());
        } finally {
            CachedFiles::writeCacheFiles();
        }
    }

    private static function getConsoleMessages($psr4Stats, $classMapStats, $filesStats, $bladeStats)
    {
        $lines = Psr4Report::getConsoleMessages($psr4Stats, $classMapStats, $filesStats);

        $lines[] = implode(PHP_EOL, [
            Reporters\BladeReport::getBladeStats($bladeStats),
        ]);

        return $lines;
    }

    private function hasFoundPatterns(): bool
    {
        return PatternRefactorings::$patternFound;
    }
}
