<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Iterators\BladeFiles;
use Imanghafoori\LaravelMicroscope\Iterators\ClassMapIterator;
use Imanghafoori\LaravelMicroscope\SearchReplace\PatternRefactorings;
use Imanghafoori\SearchReplace\PatternParser;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters;

trait PatternApply
{
    private function appliesPatterns(array $patterns, string $fileName, string $folder): void
    {
        $parsedPatterns = PatternParser::parsePatterns($patterns);

        $cb = function () use ($parsedPatterns) {
            return [$parsedPatterns];
        };

        $check = [PatternRefactorings::class];

        $psr4Stats = ForPsr4LoadedClasses::check($check, $cb, $fileName, $folder);
        $classMapStats = ClassMapIterator::iterate(base_path(), $check, $cb, $folder, $fileName);
        $bladeStats = BladeFiles::check($check, [$parsedPatterns], $fileName, $folder);

        $this->getOutput()->writeln(implode(PHP_EOL, [
            Reporters\Psr4Report::printAutoload($psr4Stats, $classMapStats),
            Reporters\BladeReport::getBladeStats($bladeStats),
        ]));
    }
}
