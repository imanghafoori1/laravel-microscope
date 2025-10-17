<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Database\Eloquent\Concerns\QueriesRelationships;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\SearchReplace\IsSubClassOf;
use Imanghafoori\SearchReplace\Filters;

class CheckDynamicWhereMethod extends BaseCommand
{
    use PatternApply;

    protected $signature = 'check:dynamic_wheres
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

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

    public function __construct()
    {
        parent::__construct();
        Filters::$filters['is_subclass_of'] = IsSubClassOf::class;
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
                'mutator' => function ($matches) {
                    $matches[1][1] = $this->deriveColumnName($matches[1][1]);

                    return $matches;
                },
            ],
            'pattern_name_2' => [
                'search' => ')-><name>(<in_between>)',
                'replace' => ')->where(<1>, <2>)',
                'filters' => [
                    1 => [
                        [$dynamicWhere, null],
                    ],
                ],
                'mutator' => function ($matches) {
                    $matches[0][1] = $this->deriveColumnName($matches[0][1]);

                    return $matches;
                },
            ],

        ];
    }

    private function deriveColumnName($methodName): string
    {
        return "'".strtolower(Str::snake(substr($methodName, 5)))."'";
    }
}
