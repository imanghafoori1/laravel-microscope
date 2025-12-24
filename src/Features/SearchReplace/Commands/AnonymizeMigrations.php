<?php

namespace Imanghafoori\LaravelMicroscope\Features\SearchReplace\Commands;

use Illuminate\Database\Migrations\Migration;
use Imanghafoori\LaravelMicroscope\Features\SearchReplace\IsEqualOrSub;
use Imanghafoori\LaravelMicroscope\Features\SearchReplace\PatternRefactorings;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\SearchReplace\Filters;
use Imanghafoori\SearchReplace\PatternParser;
use Symfony\Component\Finder\Finder;

class AnonymizeMigrations extends BaseCommand
{
    protected $signature = 'check:migrations {--s|nofix}
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $description = 'Makes migration classes anonymous.';

    public $customMsg = 'All the migration classes are anonymous.';

    public $checks = [];

    public static $laravelVersion;

    public function handleCommand($iterator, $command)
    {
        Filters::$filters['is_a'] = IsEqualOrSub::class;
        $version = self::$laravelVersion ?: app()->version();

        if (version_compare('8.37.0', $version) !== -1) {
            $command->info('Anonymous migrations are supported in laravel 8.37 and above.');
            $command->info('You are currently on laravel version: '.$version);

            return 0;
        }

        $this->patternCommand();
    }

    private function patternCommand()
    {
        $pathDTO = PathFilterDTO::makeFromOption($this);

        $this->appliesPatterns($this->parsePatterns(), $pathDTO);
    }

    private function appliesPatterns(array $patterns, PathFilterDTO $pathDTO): void
    {
        PatternRefactorings::$patterns = $patterns;

        foreach (LaravelPaths::migrationDirs() as $migrationFolder) {
            foreach ($this->getMigrationFiles($migrationFolder, $pathDTO->includeFile) as $migration) {
                PatternRefactorings::check(
                    PhpFileDescriptor::make($migration->getRealPath()),
                );
            }
        }
    }

    private function getPatterns(): array
    {
        return [
            'anonymize_migrations' => [
                'search' => 'class <name> extends <class_ref> {<in_between>}',
                'replace' => 'return new class extends <2>'.PHP_EOL.'{<3>};',
                'filters' => [
                    2 => [
                        'is_a' => Migration::class,
                    ],
                ],
            ],
        ];
    }

    private function getMigrationFiles($folder, string $fileName): Finder
    {
        return Finder::create()
            ->name(($fileName ?: '*').'.php')
            ->files()
            ->in($folder);
    }

    private function parsePatterns()
    {
        return PatternParser::parsePatterns($this->getPatterns());
    }
}
