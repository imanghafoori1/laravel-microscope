<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use DateInterval;
use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassAtMethod;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Checks\CheckClassReferencesAreValid;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\ClassAtMethodHandler;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\PrintWrongClassRefs;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\CheckImportReporter;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasesCheck;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasReplacer;
use Imanghafoori\LaravelMicroscope\Features\FacadeAlias\FacadeAliasReporter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\ForPsr4LoadedClasses;
use Imanghafoori\LaravelMicroscope\Iterators\BladeFiles;
use Imanghafoori\LaravelMicroscope\Iterators\ChecksOnPsr4Classes;
use Imanghafoori\LaravelMicroscope\Iterators\ClassMapIterator;
use Imanghafoori\LaravelMicroscope\Iterators\FileIterators;
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

        $routeFiles = FilePath::removeExtraPaths(RoutePaths::get(), $fileName, $folder);
        $classMapFiles = ComposerJson::getClassMaps(base_path());
        $autoloadedFiles = FilePath::removeExtraPaths(ComposerJson::autoloadedFilesList(base_path()), $fileName, $folder);

        $paramProvider = $this->getParamProvider();

        $checks = $this->checks;
        unset($checks[1]);

        $routeFiles = FileIterators::checkFiles($routeFiles, $paramProvider, $checks);
        $classMapStats = ClassMapIterator::iterate($classMapFiles, $paramProvider, $checks);
        $autoloadedFiles = FileIterators::checkFilePaths($autoloadedFiles, $paramProvider, $checks);

        $foldersStats = FileIterators::checkFolders(
            FileIterators::getLaravelFolders(),
            $paramProvider,
            $fileName,
            $folder,
            $checks
        );

        $psr4Stats = ForPsr4LoadedClasses::check($this->checks, $paramProvider, $fileName, $folder);
        $bladeStats = BladeFiles::check($this->checks, $paramProvider, $fileName, $folder);

        $filesCount = ChecksOnPsr4Classes::$checkedFilesCount;
        $refCount = ImportsAnalyzer::$checkedRefCount;
        $errorPrinter = ErrorPrinter::singleton($this->output);

        $messages = [];
        $messages[] = Reporters\CheckImportReporter::totalImportsMsg($refCount);

        Reporters\Psr4Report::$callback = function () use ($errorPrinter) {
            $errorPrinter->flushErrors();
        };
        $messages[] = Reporters\Psr4Report::printAutoload($psr4Stats, $classMapStats);
        $messages[] = CheckImportReporter::header();
        $filesCount && $messages[] = Reporters\CheckImportReporter::getFilesStats($filesCount);
        $messages[] = Reporters\BladeReport::getBladeStats($bladeStats);
        $messages[] = Reporters\LaravelFoldersReport::foldersStats($foldersStats);

        $routeFiles && $messages[] = CheckImportReporter::getRouteStats($routeFiles);

        $count = iterator_to_array($autoloadedFiles);
        $count && $messages[] = CheckImportReporter::getAutoloadedFiles($count);

        $messages[] = Reporters\SummeryReport::summery($errorPrinter->errorsCounts);

        if (! $refCount) {
            $messages = ['<options=bold;fg=yellow>No imports were found!</> with filter: <fg=red>"'.($fileName ?: $folder).'"</>'];
        }

        $this->finishCommand($errorPrinter);
        $this->getOutput()->writeln(implode(PHP_EOL, array_filter($messages)));

        $errorPrinter->printTime();

        if ($this->shouldRequestThanks()) {
            ErrorPrinter::thanks($this);
        }

        $this->line('');

        return $errorPrinter->hasErrors() ? 1 : 0;
    }

    private function shouldRequestThanks(): bool
    {
        $key = 'microscope_thanks_throttle';

        if (cache()->get($key)) {
            return false;
        }

        // $currentCommandName = request()->server('argv')[1] ?? '';
        $show = random_int(1, 5) === 2;
        $show && cache()->set($key, '_', DateInterval::createFromDateString('3 days'));

        return $show;
    }

    /**
     * @return \Closure
     */
    private function getParamProvider()
    {
        return function ($tokens) {
            $imports = ParseUseStatement::parseUseStatements($tokens);

            return $imports[0] ?: [$imports[1]];
        };
    }
}
