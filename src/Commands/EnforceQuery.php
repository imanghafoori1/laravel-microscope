<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\SearchReplace\IsSubClassOf;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\SearchReplace\Filters;
use JetBrains\PhpStorm\ExpectedValues;

class EnforceQuery extends Command
{
    use LogsErrors;
    use PatternApply;

    protected $signature = 'enforce:query {--f|file=} {--d|folder=} {--detailed : Show files being checked}';

    protected $description = 'Enforces the ::query() method call on models.';

    protected $customMsg = 'No case was found to add ::query()-> to it.  \(^_^)/';

    #[ExpectedValues(values: [0, 1])]
    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Soaring like an eagle...');

        Filters::$filters['is_subclass_of'] = IsSubClassOf::class;

        return $this->patternCommand($errorPrinter);
    }

    private function getPatterns(): array
    {
        return [
            'pattern_name' => [
                'search' => '<class_ref>::<name>',
                'replace' => '<1>::query()-><2>',
                'filters' => [
                    1 => [
                        'is_subclass_of' => Model::class,
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
            'selectRaw',
            'create',
            'insert',
            'limit',
            'orderBy',
            'findMany',
            'get',
        ];
    }
}
