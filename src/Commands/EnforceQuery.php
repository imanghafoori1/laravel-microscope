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

    protected $signature = 'enforce:query
        {--f|file=}
        {--d|folder=}
        {--m|methods=}
        {--c|classes=}
        {--detailed : Show files being checked}
    ';

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

    private function getPatterns()
    {
        return [
            'enforce_query' => [
                'cache_key' => 'enforce_query-v1',
                'search' => '<class_ref>::<name>',
                'replace' => '<1>::query()-><2>',
                'filters' => [
                    1 => $this->getModelConditions(),
                    2 => [
                        'in_array' => $this->getMethods(),
                    ],
                ],
            ],
        ];
    }

    private function getMethods()
    {
        $methods = ltrim($this->option('methods'), '=');

        if ($methods) {
            return explode(',', $methods);
        }

        return $this->allMethods();
    }

    private function getModelConditions()
    {
        $modelConditions = [
            'is_subclass_of' => Model::class,
        ];

        $methods = ltrim($this->option('classes'), '=');
        $methods && $modelConditions['in_array'] = explode(',', $methods);

        return $modelConditions;
    }

    private function allMethods()
    {
        return [
            'has',
            'where',
            'whereIn',
            'whereNull',
            'whereNotIn',
            'whereNotNull',
            'whereHas',
            'whereRaw',
            'count',
            'find',
            'findOr',
            'firstOr',
            'findOrFail',
            'firstOrFail',
            'firstOrCreate',
            'firstOrNew',
            'selectRaw',
            'findOrNew',
            'paginate',
            'first',
            'get',
            'pluck',
            'select',
            'create',
            'insert',
            'limit',
            'orderBy',
            'findMany',
        ];
    }
}
