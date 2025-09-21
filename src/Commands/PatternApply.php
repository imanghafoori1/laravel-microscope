<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Psr4ReportPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Iterators\BladeFiles\CheckBladePaths;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForBladeFiles;
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

        Reporters\Psr4Report::$callback = fn () => $errorPrinter->flushErrors();

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

        $checkSet = CheckSet::init([PatternRefactorings::class], $pathDTO, [$parsedPatterns]);
        $lines = Reporters\ForComposerJsonFiles::checkAndPrint($checkSet);
        CheckBladePaths::$readOnly = false;
        $lines->add(Reporters\BladeReport::getBladeStats(ForBladeFiles::check($checkSet)));

        $lines->add(PHP_EOL.CheckImportReporter::getRouteStats(ForRouteFiles::check($checkSet)));
        try {
            Psr4ReportPrinter::printAll($lines, $this->getOutput());
        } finally {
            CachedFiles::writeCacheFiles();
        }
    }

    private function hasFoundPatterns(): bool
    {
        return PatternRefactorings::$patternFound;
    }
}
