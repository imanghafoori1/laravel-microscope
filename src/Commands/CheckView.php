<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\CheckClasses;
use Imanghafoori\LaravelMicroscope\Checks\CheckClassReferences;
use Imanghafoori\LaravelMicroscope\Checks\CheckRouteCalls;
use Imanghafoori\LaravelMicroscope\Checks\CheckViewFilesExistence;
use Imanghafoori\LaravelMicroscope\CheckViewRoute;
use Imanghafoori\LaravelMicroscope\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\GetClassProperties;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;
use Imanghafoori\LaravelMicroscope\Util;
use Imanghafoori\LaravelMicroscope\View\ViewParser;
use Symfony\Component\Finder\Finder;

class CheckView extends Command
{
    use LogsErrors;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:views';

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
        $this->info('Checking views ...');

        $errorPrinter->printer = $this->output;

        $psr4 = Util::parseComposerJson('autoload.psr-4');

        foreach ($psr4 as $namespace => $path) {
            self::within($namespace, $path);
        }

        $methods = [
            [new CheckViewFilesExistence, 'check'],
            [new CheckClassReferences, 'check'],
            [new CheckRouteCalls, 'check'],
        ];
        (new CheckViewRoute)->check($methods);

        $this->finishCommand($errorPrinter);
    }

    public static function within($namespace, $path)
    {
        static::checkAllClasses((new Finder)->files()->in(base_path($path)), base_path(), $path, $namespace);
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
    protected static function checkAllClasses($classes, $basePath, $composerPath, $composerNamespace)
    {
        foreach ($classes as $classFilePath) {
            $absFilePath = $classFilePath->getRealPath();
//            $classPath = trim(Str::replaceFirst($basePath, '', $absFilePath), DIRECTORY_SEPARATOR);
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
                if (is_subclass_of($currentNamespace.'\\'.$class, Controller::class)) {
                    self::checkViews($currentNamespace.'\\'.$class);
                }
            }
        }
    }

    /**
     * @param $method
     * @param $ctrl
     */
    protected static function checkViews($ctrl)
    {
        $methods = self::get_class_methods(new \ReflectionClass($ctrl));
        foreach ($methods as $method) {
            $vParser = new ViewParser($method);
            $views = $vParser->retrieveViewsFromMethod();

            self::checkView($ctrl, $method, $views);
        }
    }

    protected static function checkView($ctrl, $method, array $views)
    {
        foreach ($views as $view => $_) {
            if (! Str::contains($_['name'], ['$', '->', ' ']) && ! View::exists($_['name'])) {
                app(ErrorPrinter::class)->view($_['file'], $_['line'], $_['lineNumber'], $_['name']);
            }

            /* if (Str::contains($_['name'], ['$', '->'])) {
                 app(ErrorPrinter::class)->view($_['file'], $_['line'], $_['lineNumber'], $_['name']);
                 sleep(1);
             }*/
        }
    }

    public static function get_class_methods(\ReflectionClass $classReflection)
    {
        $className = $classReflection->getName();
        $rm = $classReflection->getMethods();

        $functions = [];
        foreach ($rm as $f) {
            ($f->class === $className) && $functions[] = $f;
        }

        return $functions;
    }
}
