<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\GetClassProperties;
use Imanghafoori\LaravelMicroscope\Analyzers\Util;
use Imanghafoori\LaravelMicroscope\CheckBladeFiles;
use Imanghafoori\LaravelMicroscope\CheckBladeFiles;
use Imanghafoori\LaravelMicroscope\CheckClasses;
use Imanghafoori\LaravelMicroscope\Checks\CheckClassReferences;
use Imanghafoori\LaravelMicroscope\Checks\CheckRouteCalls;
use Imanghafoori\LaravelMicroscope\Checks\CheckViewFilesExistence;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\View\ViewParser;

class CheckViews extends Command
{
    use LogsErrors;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:views {--d|detailed : Show files being checked}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the validity of blade files';

    /**
     * Execute the console command.
     *
     * @param  ErrorPrinter  $errorPrinter
     *
     * @return mixed
     */
    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking views...');

        $errorPrinter->printer = $this->output;

        $psr4 = Util::parseComposerJson('autoload.psr-4');

        foreach ($psr4 as $namespace => $path) {
            $this->checkAllClasses(FilePath::getAllPhpFiles($path));
        }

        $checks = [
            [CheckViewFilesExistence::class, 'check'],
            [CheckClassReferences::class, 'check'],
            [CheckRouteCalls::class, 'check'],
        ];

        CheckBladeFiles::applyChecks($checks);

        $this->finishCommand($errorPrinter);
    }

    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $classes
     * @param  string  $basePath
     *
     * @param $composerPath
     * @param $composerNamespace
     *
     * @return void
     */
    public function checkAllClasses($classes)
    {
        foreach ($classes as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();

            if (! CheckClasses::hasOpeningTag($absFilePath)) {
//                app(ErrorPrinter::class)->print('Skipped file: '.$classPath);
                continue;
            }

            [
                $currentNamespace,
                $class,
                $type,
            ] = GetClassProperties::fromFilePath($absFilePath);

            if ($class) {
                $this->checkViewsMake($currentNamespace.'\\'.$class);
            }
        }
    }

    /**
     * @param $method
     * @param $class
     */
    protected function checkViewsMake($class)
    {
        $methods = self::get_class_methods(new \ReflectionClass($class));
        foreach ($methods as $method) {
            $vParser = new ViewParser($method);
            $views = $vParser->retrieveViewsFromMethod();

            if ($this->option('detailed')) {
                $this->line("Checking {$method->name} on {$method->class}");
            }

            self::checkView($views);
        }
    }

    protected static function checkView($views)
    {
        foreach ($views as $view => $_) {
            // in order to exclude dynamic parameters like: view($myView)
            if (! Str::contains($_['name'], ['$', '->', ' ']) && ! View::exists($_['name'])) {
                app(ErrorPrinter::class)->view($_['file'], $_['line'], $_['lineNumber'], $_['name']);
            }
        }
    }

    public static function get_class_methods($classReflection)
    {
        $className = $classReflection->getName();
        $methods = $classReflection->getMethods();

        $functions = [];
        foreach ($methods as $f) {
            ($f->class === $className) && $functions[] = $f;
        }

        return $functions;
    }
}
