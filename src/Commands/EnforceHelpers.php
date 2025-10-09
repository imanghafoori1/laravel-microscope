<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Support\Facades\Facade;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\SearchReplace\FullNamespaceIs;
use Imanghafoori\LaravelMicroscope\SearchReplace\IsSubClassOf;
use Imanghafoori\LaravelMicroscope\SearchReplace\NamespaceIs;
use Imanghafoori\SearchReplace\Filters;

class EnforceHelpers extends BaseCommand
{
    use PatternApply;

    protected $signature = 'enforce:helper_functions
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $description = 'Enforces helper functions over laravel internal facades.';

    protected $customMsg = 'No facade was found to be replaced by helper functions.  \(^_^)/';

    public function __construct()
    {
        parent::__construct();
        Filters::$filters['is_subclass_of'] = IsSubClassOf::class;
        Filters::$filters['full_namespace_pattern'] = FullNamespaceIs::class;
        Filters::$filters['namespace_pattern'] = NamespaceIs::class;
    }

    private function getPatterns(): array
    {
        $mutator = function ($matches) {
            $matches[0][1] = strtolower($matches[0][1]);

            return $matches;
        };

        return [
            'full_facade_paths' => [
                'cache_key' => 'full_facade_paths-v1',
                'search' => '<class_ref>::',
                'replace' => '<1>()->',
                'filters' => [
                    1 => [
                        'full_namespace_pattern' => 'Illuminate\\Support\\*',
                        'is_subclass_of' => Facade::class,
                        'in_array' => ['Auth', 'Session', 'Config', 'Cache', 'Redirect', 'Request'],
                    ],
                ],
                'mutator' => $mutator,
            ],
            'facade_aliases' => [
                'cache_key' => 'facade_aliases-v1',
                'search' => '<class_ref>::',
                'replace' => '<1>()->',
                'filters' => [
                    1 => [
                        'namespace_pattern' => '',
                        'in_array' => ['Auth', 'Session', 'Config', 'Cache', 'Redirect', 'Request'],
                    ],
                ],
                'mutator' => $mutator,
            ],
        ];
    }
}
