<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Concerns\QueriesRelationships;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\SearchReplace\IsSubClassOf;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\SearchReplace\Filters;

class CheckDynamicWhereMethod extends Command
{
    use LogsErrors;
    use PatternApply;

    protected $signature = 'check:dynamic_wheres {--f|file=} {--d|folder=}';

    protected $description = 'Enforces the non-dynamic where clauses.';

    protected $customMsg = 'No dynamic where clause was found!   \(^_^)/';

    protected $excludeMethods = [
        'whereKey',
        'whereHas',
        'whereHasMorph',
        'whereRelation',
        'whereDoesntHave',
        'whereDoesntHaveMorph',
        'whereMorphRelation',
        'whereMorphedTo',
        'whereBelongsTo',
        'whereColumn',
        'whereRaw',
        'whereIn',
        'whereNotIn',
        'whereIntegerInRaw',
        'whereIntegerNotInRaw',
        'whereNotBetweenColumns',
        'whereBetweenColumns',
        'whereNotBetween',
        'whereNotNull',
        'whereBetween',
        'whereNull',
        'whereDate',
        'whereTime',
        'whereDay',
        'whereYear',
        'whereMonth',
        'whereNested',
        'whereExists',
        'whereNotExists',
        'whereRowValues',
        'whereJsonContains',
        'whereJsonDoesntContain',
        'whereJsonLength',
        'whereFullText',
        'whereNot',
        'whereAny',
        'whereStrict',
        'whereInStrict',
        'whereInstanceOf',
        'whereNotInStrict',
    ];

    public function handle(ErrorPrinter $errorPrinter)
    {
        event('microscope.start.command');
        $this->info('Soaring like an eagle...');

        Filters::$filters['is_subclass_of'] = IsSubClassOf::class;

        return $this->patternCommand($errorPrinter);
    }

    private function getPatterns(): array
    {
        $dynamicWhere = function ($matchedToken) {
            return strlen($matchedToken[1]) > 5
                && Str::startsWith($matchedToken[1], ['where'])
                && ! in_array($matchedToken[1], $this->excludeMethods)
                && ! method_exists(Builder::class, $matchedToken[1])
                && ! method_exists(QueriesRelationships::class, $matchedToken[1]);
        };

        $mutator = function ($matches) {
            $matches[0][1] = $this->deriveColumnName($matches[0][1]);

            return $matches;
        };

        return [
            'pattern_name_1' => [
                'search' => '<class_ref>::<name>(',
                'replace' => '<1>::query()->where(<2>, ',
                'filters' => [
                    1 => [
                        'is_subclass_of' => Model::class,
                    ],
                    2 => [
                        [$dynamicWhere, null],
                    ],
                ],
                'mutator' => $mutator,
            ],
            'pattern_name_2' => [
                'search' => ')-><name>(<in_between>)',
                'replace' => ')->where(<1>, <2>)',
                'filters' => [
                    1 => [
                        [$dynamicWhere, null],
                    ],
                ],
                'mutator' => $mutator,
            ],

        ];
    }

    private function deriveColumnName($methodName): string
    {
        return "'".strtolower(Str::snake(substr($methodName, 5)))."'";
    }
}
