<?php

namespace Imanghafoori\LaravelMicroscope\Features\SearchReplace;

use Imanghafoori\LaravelMicroscope\Foundations\Iterators\BladeFiles\CheckBladePaths;
use Imanghafoori\LaravelMicroscope\Foundations\Reports\ComposerJsonReport;
use Imanghafoori\SearchReplace\PatternParser;

trait PatternApply
{
    public $initialMsg = 'Soaring like an eagle...';

    public $checks = [PatternRefactorings::class];

    abstract public function getPatterns();

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        ComposerJsonReport::$callback = fn () => $this->errorPrinter->flushErrors();

        $patterns = $this->getPatterns();

        $this->appliesPatterns($patterns, $iterator);
    }

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    private function appliesPatterns(array $patterns, $iterator)
    {
        CheckBladePaths::$readOnly = false;
        PatternRefactorings::$patterns = PatternParser::parsePatterns($patterns);

        $iterator->printAll([
            $iterator->forComposerLoadedFiles(),
            $iterator->forBladeFiles(),
            PHP_EOL.$iterator->forRoutes(),
        ]);
        CheckBladePaths::$readOnly = true;
    }
}
