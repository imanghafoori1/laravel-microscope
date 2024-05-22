<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassAtMethod;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\ClassAtMethodHandler;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\PrintWrongClassRefs;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\AutoloadFiles;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasesCheck;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasReplacer;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasReporter;
use Imanghafoori\LaravelMicroscope\Features\Thanks;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\BladeFiles;
use Imanghafoori\LaravelMicroscope\Iterators\ChecksOnPsr4Classes;
use Imanghafoori\LaravelMicroscope\Iterators\ClassMapIterator;
use Imanghafoori\LaravelMicroscope\Iterators\FileIterators;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class CheckImportsCommand extends Command
{
    use LogsErrors;

    protected $signature = 'check:imports
        {--force : fixes without asking}
        {--w|wrong : Only reports wrong imports}
        {--e|extra : Only reports extra imports}
        {--f|file= : Pattern for file names to scan}
        {--d|folder= : Pattern for file names to scan}
        {--s|nofix : avoids the automatic fixes}
    ';

    protected $description = 'Checks the validity of use statements';

    protected $customMsg = '';

    /**
     * @var array<int, class-string<\Imanghafoori\LaravelMicroscope\Iterators\Check>>
     */
    private $checks = [
        1 => CheckClassAtMethod::class,
        2 => CheckClassReferencesAreValid::class,
        3 => FacadeAliasesCheck::class,
    ];

    public function handle()
    {
        event('microscope.start.command');
        $this->line('');
        $this->info('Checking imports and class references...');

        FacadeAliasesCheck::$command = $this->getOutput();

        if ($this->option('nofix')) {
            ClassAtMethodHandler::$fix = false;
            FacadeAliasesCheck::$handler = FacadeAliasReporter::class;
            CheckClassReferencesAreValid::$wrongClassRefsHandler = PrintWrongClassRefs::class;
        }

        if ($this->option('force')) {
            FacadeAliasReplacer::$forceReplace = true;
        }

        if ($this->option('wrong')) {
            CheckClassReferencesAreValid::$checkExtra = false;
            unset($this->checks[3]); // avoid checking facades
        }

        if ($this->option('extra')) {
            CheckClassReferencesAreValid::$checkWrong = false;
            unset($this->checks[3]); // avoid checking facades
        }

        $fileName = ltrim($this->option('file'), '=');
        $folder = ltrim($this->option('folder'), '=');
        $folder = rtrim($folder, '/\\');

        $routeFiles = FilePath::removeExtraPaths(
            RoutePaths::get(),
            $folder,
            $fileName
        );

        $autoloadedFilesGen = FilePath::removeExtraPaths(
            ComposerJson::autoloadedFilesList(base_path()),
            $folder,
            $fileName
        );

        $paramProvider = $this->getParamProvider();

        $checks = $this->checks;
        unset($checks[1]);

        $classMapStats = ClassMapIterator::iterate(base_path(), $checks, $paramProvider, $fileName, $folder);

        $routeFiles = FileIterators::checkFiles($routeFiles, $paramProvider, $checks);
        $autoloadedFilesGen = FileIterators::checkFilePaths($autoloadedFilesGen, $paramProvider, $checks);

        $foldersStats = FileIterators::checkFolders(
            $checks,
            $this->getLaravelFolders(),
            $paramProvider,
            $fileName,
            $folder
        );

        $psr4Stats = ForPsr4LoadedClasses::check($this->checks, $paramProvider, $fileName, $folder);

        $checks = $this->checks;
        unset($checks[3]); // avoid checking facades aliases in blade files.
        $bladeStats = BladeFiles::check($checks, $paramProvider, $fileName, $folder);

        $errorPrinter = ErrorPrinter::singleton($this->output);
        Reporters\Psr4Report::$callback = function () use ($errorPrinter) {
            $errorPrinter->flushErrors();
        };

        $messages = [];
        $messages[0] = CheckImportReporter::totalImportsMsg();
        $messages[1] = Reporters\Psr4Report::printAutoload($psr4Stats, $classMapStats);
        $messages[2] = CheckImportReporter::header();
        $messages[3] = $this->getFilesStats();
        $messages[4] = Reporters\BladeReport::getBladeStats($bladeStats);
        $messages[5] = Reporters\LaravelFoldersReport::foldersStats($foldersStats);
        $messages[6] = CheckImportReporter::getRouteStats($routeFiles);
        $messages[7] = AutoloadFiles::getLines($autoloadedFilesGen);
        $messages[8] = Reporters\SummeryReport::summery($errorPrinter->errorsCounts);

        if (! ImportsAnalyzer::$checkedRefCount) {
            $messages = ['<options=bold;fg=yellow>No imports were found!</> with filter: <fg=red>"'.($fileName ?: $folder).'"</>'];
        }

        $this->finishCommand($errorPrinter);
        $this->getOutput()->writeln(implode(PHP_EOL, array_filter($messages)));

        $errorPrinter->printTime();

        if (Thanks::shouldShow()) {
            $this->printThanks($this);
        }

        $this->line('');

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private function printThanks($command)
    {
        $command->line(PHP_EOL);
        foreach (Thanks::messages() as $msg) {
            $command->line($msg);
        }
    }

    /**
     * @return \Closure
     */
    private function getParamProvider()
    {
        return function (PhpFileDescriptor $file) {
            $imports = ParseUseStatement::parseUseStatements($file->getTokens());

            return $imports[0] ?: [$imports[1]];
        };
    }

    private function getFilesStats()
    {
        $filesCount = ChecksOnPsr4Classes::$checkedFilesCount;

        return $filesCount ? CheckImportReporter::getFilesStats($filesCount) : '';
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
