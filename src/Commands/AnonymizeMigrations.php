<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\SearchReplace\PatternRefactorings;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\SearchReplace\PatternParser;
use JetBrains\PhpStorm\ExpectedValues;
use Symfony\Component\Finder\Finder;

class AnonymizeMigrations extends Command
{
    use LogsErrors;

    protected $signature = 'check:migrations {--s|nofix} {--f|file=} {--d|folder=}';

    protected $description = 'Makes migration classes anonymous.';

    #[ExpectedValues(values: [0, 1])]
    public function handle()
    {
        if (version_compare('8.37.0', app()->version()) !== -1) {
            $this->info('Anonymous migrations are supported in laravel 8.37 and above.');
            $this->info('You are currently on laravel version: '.app()->version());

            return 0;
        }

        event('microscope.start.command');

        $errorPrinter = ErrorPrinter::singleton($this->output);

        $this->patternCommand($errorPrinter);

        return ErrorPrinter::singleton()->hasErrors() ? 1 : 0;
    }

    private function patternCommand(ErrorPrinter $errorPrinter): int
    {
        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');

        $errorPrinter->printer = $this->output;

        $this->appliesPatterns($this->parsePatterns(), $fileName, $folder);

        $this->finishCommand($errorPrinter);

        $errorPrinter->printTime();

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private function appliesPatterns(array $patterns, string $fileName): void
    {
        foreach ($this->filterVendorFolders($this->getMigrationFolders()) as $migrationFolder) {
            foreach ($this->getMigrationFiles($migrationFolder, $fileName) as $migration) {
                PatternRefactorings::check(
                    PhpFileDescriptor::make($migration->getRealPath()),
                    $patterns
                );
            }
        }
    }

    private function getPatterns(): array
    {
        return [
            'anonymize_migrations' => [
                'search' => 'class "<1:name>" extends "<2:class_ref>"{<3:in_between>}',
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
        return [PatternParser::parsePatterns($this->getPatterns())];
    }
}
