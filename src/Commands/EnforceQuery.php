<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\SearchReplace\IsSubClassOf;
use Imanghafoori\LaravelMicroscope\SearchReplace\PatternRefactorings;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\SearchReplace\Filters;
use Imanghafoori\SearchReplace\PatternParser;

class EnforceQuery extends Command
{
    use LogsErrors;

    protected $signature = 'enforce:query {--f|file=} {--d|folder=} {--detailed : Show files being checked} {--s|nofix : avoids the automatic fixes}';

    protected $description = 'Enforces the ::query() method call on models.';

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Soaring like an eagle...');

        $this->option('nofix') && config(['microscope.no_fix' => true]);

        $errorPrinter->printer = $this->output;

        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');
        Filters::$filters['is_sub_class_of'] = IsSubClassOf::class;

        app()->singleton('current.command', function () {
            return $this;
        });

        $errorPrinter->printer = $this->output;

        $patterns = $this->getPatterns();
        $parsedPatterns = PatternParser::parsePatterns($patterns);

        ForPsr4LoadedClasses::check([PatternRefactorings::class], [$parsedPatterns, $patterns], $fileName, $folder);

        // Checks the blade files for class references.
        // BladeFiles::check([PatternRefactorings::class], $fileName, $folder);

        $this->finishCommand($errorPrinter);

        $errorPrinter->printTime();

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private function getPatterns(): array
    {
        return [
            'pattern_name' => [
                'search' => '<class_ref>::<name>',
                'replace' => '<1>::query()-><2>',
                'filters' => [
                    1 => [
                        'is_sub_class_of' => Model::class,
                    ],
                    2 => [
                        'in_array' => $this->getMethods(),
                    ],
                ],
            ],
        ];
    }

    private function getMethods(): array
    {
        return [
            'where',
            'whereIn',
            'whereNotIn',
            'whereNull',
            'whereNotNull',
            'whereHas',
            'whereRaw',
            'count',
            'find',
            'findOr',
            'firstOr',
            'firstOrCreate',
            'findOrFail',
            'firstOrFail',
            'paginate',
            'findOrNew',
            'first',
            'pluck',
            'firstOrNew',
            'select',
            'create',
            'insert',
            'findMany',
        ];
    }
}
