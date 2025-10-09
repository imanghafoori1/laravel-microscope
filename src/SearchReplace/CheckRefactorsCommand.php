<?php

namespace Imanghafoori\LaravelMicroscope\SearchReplace;

use ErrorException;
use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\Psr4ReportPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\ForComposerJsonFiles;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\SearchReplace\Filters;
use Imanghafoori\SearchReplace\PatternParser;

class CheckRefactorsCommand extends Command
{
    protected $signature = 'search_replace
     {--N|name=}
     {--t|tag=}
     {--f|file=}
     {--d|folder=}
     {--F|except-file= : Comma seperated patterns for file names to exclude}
     {--D|except-folder= : Comma seperated patterns for folder names to exclude}
     {--s|nofix}';

    protected $description = 'Searches for the code patterns and replaces them accordingly.';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking for refactors...');

        Filters::$filters['is_sub_class_of'] = IsSubClassOf::class;

        $errorPrinter->printer = $this->output;

        try {
            $patterns = require base_path('/search_replace.php');
        } catch (ErrorException $e) {
            file_put_contents(base_path('/search_replace.php'), $this->stub());

            $this->getOutput()->writeln('The "search_replace.php" was created.');

            return;
        }

        $patterns = $this->filter($this->option('name'), $this->option('tag'), $patterns);

        if ($this->option('nofix')) {
            foreach ($patterns as &$pattern) {
                unset($pattern['replace']);
            }
        }

        if (! $patterns) {
            $this->getOutput()->writeln('No pattern found...');

            return;
        }

        $patterns = $this->normalizePatterns($patterns);
        $parsedPatterns = PatternParser::parsePatterns($patterns);
        $params = [$parsedPatterns, $patterns];

        $checkSet = CheckSet::initParams([PatternRefactorings::class], $this, $params);

        $lines = ForComposerJsonFiles::checkAndPrint($checkSet);
        Psr4ReportPrinter::printAll($lines, $this->getOutput());

        $this->getOutput()->writeln(' - Finished search/replace');

        return PatternRefactorings::$patternFound ? 1 : 0;
    }

    private function stub()
    {
        return file_get_contents(__DIR__.'/search_replace.stub');
    }

    private function normalizePatterns($refactors)
    {
        foreach ($refactors as $i => $ref) {
            isset($ref['directory']) && $refactors[$i]['directory'] = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $ref['directory']);
        }

        return $refactors;
    }

    private function filter($name, $tag, $patterns)
    {
        if ($name && isset($patterns[$name])) {
            return [$name => $patterns[$name]];
        }

        if ($tag) {
            $filteredPatterns = [];
            foreach ($patterns as $name => $pattern) {
                if (isset($pattern['tags'])) {
                    $tags = $pattern['tags'];
                    is_string($tags) && $tags = explode(',', $tags);
                    if (in_array($tag, $tags)) {
                        $filteredPatterns[$name] = $pattern;
                    }
                }
            }

            return $filteredPatterns;
        }

        return $patterns;
    }
}
