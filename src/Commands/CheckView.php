<?php

namespace Imanghafoori\LaravelSelfTest\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;
use Symfony\Component\Finder\Finder;
use Illuminate\Routing\Controller;
use Imanghafoori\LaravelSelfTest\ErrorPrinter;
use Imanghafoori\LaravelSelfTest\CheckClasses;
use Imanghafoori\LaravelSelfTest\View\ViewParser;
use Imanghafoori\LaravelSelfTest\GetClassProperties;

class CheckView extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the validity of blade files';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $composer = json_decode(file_get_contents(app()->basePath('composer.json')), true);
        $psr4 = (array) data_get($composer, 'autoload.psr-4');

        foreach ($psr4 as $namespace => $path) {
            self::within($namespace, $path);
        }
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
            $classPath = trim(Str::replaceFirst($basePath, '', $absFilePath), DIRECTORY_SEPARATOR);
            if (! CheckClasses::hasOpeningTag($absFilePath)) {
                app(ErrorPrinter::class)->print('Skipped file: ' .$classPath);
                continue;
            }
            [
                $currentNamespace,
                $class,
                $type,
            ] = GetClassProperties::fromFilePath($absFilePath);

            if (is_subclass_of($currentNamespace.'\\'.$class, Controller::class)) {

                self::checkViews($currentNamespace.'\\'.$class);
            }

        }
    }

    /**
     * @param $method
     * @param $ctrl
     */
    protected static function checkViews($ctrl)
    {
        $methods = self::get_class_methods(new \ReflectionClass($class));

        foreach ($methods as $method) {
            $vParser = new ViewParser($method);
            $views = $vParser->parse()->getChildren();


            self::checkView($ctrl, $method, $views);
        }
    }

    protected static function checkView($ctrl, $method, array $views)
    {
        foreach ($views as $view => $_) {
            if ($_['children']) {
                self::checkView($ctrl, $method, $_['children']);
            }

            if (! $_['children']) {
                if (! View::exists($_['name'])) {
                    app(ErrorPrinter::class)->view($_['file'], $_['line'], $_['lineNumber'], $_['name']);
                }
            }
        }
    }

    static function get_class_methods(\ReflectionClass $classReflection)
    {
        $className = $classReflection->getName();
        $rm = $classReflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        $functions = [];
        foreach ($rm as $f) {
            ($f->class === $className) && $functions[] = $f;
        }

        return $functions;
    }
}
