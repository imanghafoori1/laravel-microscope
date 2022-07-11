<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Concerns\QueriesRelationships;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\SearchReplace\IsSubClassOf;
use Imanghafoori\LaravelMicroscope\SearchReplace\PatternRefactorings;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\SearchReplace\Filters;
use Imanghafoori\SearchReplace\PatternParser;

class CheckDynamicWhereMethod extends Command
{
    use LogsErrors;

    protected $signature = 'check:dynamic_wheres {--f|file=} {--d|folder=} {--detailed : Show files being checked} {--s|nofix : avoids the automatic fixes}';

    protected $description = 'Enforces the non-dynamic where clauses.';

    protected $excludeMethods = [
        'whereHas',
        'whereDoesntHave',
        'whereHasMorph',
        'whereDoesntHaveMorph',
        'whereRelation',
        'whereMorphRelation',
        'whereMorphedTo',
        'whereBelongsTo',
        'whereColumn',
        'whereRaw',
        'whereIn',
        'whereNotIn',
        'whereIntegerInRaw',
        'whereIntegerNotInRaw',
        'whereNull',
        'whereNotNull',
        'whereBetween',
        'whereBetweenColumns',
        'whereNotBetween',
        'whereNotBetweenColumns',
        'whereDate',
        'whereTime',
        'whereDay',
        'whereMonth',
        'whereYear',
        'whereNested',
        'whereExists',
        'whereNotExists',
        'whereRowValues',
        'whereJsonContains',
        'whereJsonDoesntContain',
        'whereJsonLength',
        'whereFullText',
        'whereNot',
        'whereInstanceOf',
        'whereStrict',
        'whereInStrict',
        'whereNotInStrict',
    ];

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
                'search' => '::<name>(',
                'replace' => '::query()->where(<1>, ',
                'filters' => [
                    1 => [
                        [$dynamicWhere, null],
                    ],
                ],
                'mutator' => $mutator,
            ],
            'pattern_name_3' => [
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
