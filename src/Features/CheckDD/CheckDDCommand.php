<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDD;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\LaravelFoldersReport;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\ClassMapIterator;
use Imanghafoori\LaravelMicroscope\Iterators\FileIterators;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;

class CheckDDCommand extends Command
{
    public static $checkedCallsNum = 0;

    protected $signature = 'check:dd {--f|file=} {--d|folder=}';

    protected $description = 'Checks the debug functions.';

    public function handle()
    {
        event('microscope.start.command');
        $this->info('Checking dd...');

        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');

        $paramProvider = function (PhpFileDescriptor $file, $token) {
            ErrorPrinter::singleton()->simplePendError(
                $token[1], $file->getTokens(), $token[2], 'ddFound', 'Debug function found: '
            );
        };

        $psr4Stats = ForPsr4LoadedClasses::check([CheckDD::class], [$paramProvider], $fileName, $folder);
        $classMapStats = ClassMapIterator::iterate(base_path(), [CheckDD::class], [$paramProvider], $fileName, $folder);

        $foldersStats = FileIterators::checkFolders(
            [CheckDD::class], $this->getLaravelFolders(), [$paramProvider], $fileName, $folder
        );

        $this->getOutput()->writeln(implode(PHP_EOL, [
            Psr4Report::printAutoload($psr4Stats, $classMapStats),
            LaravelFoldersReport::foldersStats($foldersStats),
        ]));

        $this->getOutput()->writeln(' - Finished looking for debug functions. ('.self::$checkedCallsNum.' files checked)');

        event('microscope.finished.checks', [$this]);

        return app(ErrorPrinter::class)->hasErrors() ? 1 : 0;
    }

    /**
     * @return array<string, \Generator>
     */
    private function getLaravelFolders()
    {
        return [
            'config' => LaravelPaths::configDirs(),
            'migrations' => LaravelPaths::migrationDirs(),
        ];
    }
}
