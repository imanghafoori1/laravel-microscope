<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\SearchReplace\PatternRefactorings;
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

    public function handleCommand()
    {
        if (version_compare('8.37.0', app()->version()) !== -1) {
            $this->info('Anonymous migrations are supported in laravel 8.37 and above.');
            $this->info('You are currently on laravel version: '.app()->version());

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

        foreach ($this->filterVendorFolders($this->getMigrationFolders()) as $migrationFolder) {
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
                        'is_sub_class_of' => \Illuminate\Database\Migrations\Migration::class,
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

    private function filterVendorFolders(array $paths): array
    {
        foreach ($paths as $key => $path) {
            if (Str::startsWith($path, base_path('vendor'))) {
                unset($paths[$key]);
            }
        }

        return $paths;
    }

    private function getMigrationFolders(): array
    {
        $paths = array_merge(app('migrator')->paths(), [database_path('migrations')]);

        return array_unique($paths);
    }

    private function parsePatterns()
    {
        return PatternParser::parsePatterns($this->getPatterns());
    }
}
