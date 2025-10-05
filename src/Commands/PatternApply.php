<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;
use Imanghafoori\LaravelMicroscope\Iterators\BladeFiles\CheckBladePaths;
use Imanghafoori\LaravelMicroscope\SearchReplace\PatternRefactorings;
use Imanghafoori\SearchReplace\PatternParser;

trait PatternApply
{
    public $initialMsg = 'Soaring like an eagle...';

    public $checks = [PatternRefactorings::class];

    abstract public function getPatterns();

    public function handleCommand()
    {
        Reporters\Psr4Report::$callback = fn () => $this->errorPrinter->flushErrors();

        $patterns = $this->getPatterns();

        $this->appliesPatterns($patterns);
    }

    /**
     * @return void
     */
    private function appliesPatterns(array $patterns)
    {
        CheckBladePaths::$readOnly = false;
        $parsedPatterns = PatternParser::parsePatterns($patterns);
        $this->params = [$parsedPatterns];
        $this->checkSet = $this->getCheckSet();
        $lines = [
            $this->forComposerLoadedFiles(),
            $this->forBladeFiles(),
            PHP_EOL.$this->forRoutes(),
        ];
        $this->printAll($lines);
        CheckBladePaths::$readOnly = true;
    }
}
