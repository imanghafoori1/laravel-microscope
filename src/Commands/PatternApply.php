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

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterator  $iterator
     * @return void
     */
    public function handleCommand($iterator)
    {
        Reporters\Psr4Report::$callback = fn () => $this->errorPrinter->flushErrors();

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
