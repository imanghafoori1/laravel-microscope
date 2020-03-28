<?php

namespace Imanghafoori\LaravelSelfTest;

use SplFileInfo;
use ReflectionClass;
use ReflectionException;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\LaravelSelfTest\View\ModelParser;

class DiscoverClasses
{
    protected static $fixedNamespaces = [];

    /**
     * Get all of the events and listeners by searching the given listener directory.
     *
     * @param  string  $path
     * @param  string  $namespace
     *
     * @return void
     */
    public static function within($path, $namespace)
    {
        static::checkAllClasses((new Finder)->files()->in(base_path($path)), base_path(), $path, $namespace);
    }

    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $classes
     * @param  string  $basePath
     *
     * @param $path
     * @param $rootNamespace
     *
     * @return void
     */
    protected static function checkAllClasses($classes, $basePath, $path, $rootNamespace)
    {
        foreach ($classes as $classFilePath) {
            try {
                $t = static::classFromFile($classFilePath, $basePath);
                if (self::hasOpeningTag($classFilePath->getRealPath())) {
                    $ref = new ReflectionClass($t);
                    self::checkImportedClassed($ref);
                    self::checkModelsRelations($t, $ref);
                }
            } catch (ReflectionException $e) {
                [
                    $incorrectNamespace,
                    $class,
                    $type,
                ] = self::getClass($classFilePath->getRealPath());

                $incorrectNamespace = ltrim($incorrectNamespace, '\\');
                if (! $class) {
                    continue;
                }

                $classPath = trim(Str::replaceFirst($basePath, '', $classFilePath->getRealPath()), DIRECTORY_SEPARATOR);

                $p = explode(DIRECTORY_SEPARATOR, $classPath);
                array_pop($p);
                $p = implode('\\', $p);
                $correctNamespace = str_replace(trim($path, '\\//'), trim($rootNamespace, '\\/'), $p);

                self::errorOut($classPath, $correctNamespace);
                self::correctNamespace($classFilePath->getRealPath(), $incorrectNamespace, $correctNamespace);
                /*static::$fixedNamespaces[$incorrectNamespace] = [
                    'class' => $class,
                    'correct_namespace' => $correctNamespace
                ];*/

                app(ErrorPrinter::class)->print('/********************************************/');
            }
        }
    }

    public static function getClass(string $file)
    {
        $fp = fopen($file, 'r');
        $type = $class = $namespace = $buffer = '';
        $i = 0;
        while (! $class) {
            if (feof($fp)) {
                break;
            }

            $buffer .= fread($fp, 512);
            $tokens = token_get_all($buffer);

            if (strpos($buffer, '{') === false) {
                continue;
            }

            for (; $i < count($tokens); $i++) {
                if ($tokens[$i][0] === T_NAMESPACE) {
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if ($tokens[$j][0] === T_STRING) {
                            $namespace .= '\\'.$tokens[$j][1];
                        } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                            break;
                        }
                    }
                }

                if (($tokens[$i][0] === T_CLASS) || $tokens[$i][0] === T_INTERFACE) {
                    $type = $tokens[$i][0] === T_CLASS ? 'class' : 'interface';
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if ($tokens[$j] === '{') {
                            $class = $tokens[$i + 2][1];
                        }
                    }
                }
            }
        }

        return [
            $namespace,
            $class,
            $type,
        ];
    }

    public static function hasOpeningTag(string $file)
    {
        $fp = fopen($file, 'r');

        if (feof($fp)) {
            return false;
        }

        $buffer = fread($fp, 51);

        return (strpos($buffer, '<?php') !== false);
    }

    /**
     * Extract the class name from the given file path.
     *
     * @param  \SplFileInfo  $file
     * @param  string  $basePath
     *
     * @return string
     */
    protected static function classFromFile(SplFileInfo $file, $basePath)
    {
        $class = trim(Str::replaceFirst($basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

        return str_replace([
            DIRECTORY_SEPARATOR,
            ucfirst(basename(app()->path())).'\\',
        ], [
            '\\',
            app()->getNamespace(),
        ], ucfirst(Str::replaceLast('.php', '', $class)));
    }

    /**
     * @param  string  $t
     * @param  \ReflectionClass  $ref
     */
    protected static function checkModelsRelations(string $t, ReflectionClass $ref)
    {
        if (is_subclass_of($t, Model::class)) {
            foreach ($ref->getMethods() as $method) {
                $errors = (new ModelParser())->retrieveFromMethod($method);
                foreach ($errors as $err) {
                    app(ErrorPrinter::class)->print(' - Wrong model is passed in relation');
                    app(ErrorPrinter::class)->print($err['file']);
                    app(ErrorPrinter::class)->print('line: '. $err['lineNumber'].'       '.trim($err['line']));
                    app(ErrorPrinter::class)->print($err['name'].' is not a valid class.');
                    app(ErrorPrinter::class)->print('/********************************************/');
                }
            }
        }
    }

    private static function checkImportedClassed(ReflectionClass $ref)
    {
        $imports = ParseUseStatement::getUseStatements($ref);

        foreach ($imports as $i => $imp) {
            if (! class_exists($imp[0]) && ! interface_exists($imp[0]) && ! trait_exists($imp[0])) {
                $err = $ref->getName();
                app(ErrorPrinter::class)->print(' - Wrong import');
                app(ErrorPrinter::class)->print($err);
                app(ErrorPrinter::class)->print('line: '. $imp[1].'     use '.$imp[0].';');
                app(ErrorPrinter::class)->print('/********************************************/');
            }
        }
    }

    /**
     * @param  string  $classFilePath
     * @param  string  $incorrectNamespace
     * @param  string  $correctNamespace
     */
    protected static function correctNamespace($classFilePath, string $incorrectNamespace, string $correctNamespace)
    {
        $newline = "namespace ".$correctNamespace.';'.PHP_EOL;
        $search = ltrim($incorrectNamespace, '\\');
        ReplaceLine::replace($classFilePath, $search, $newline);
    }

    /**
     * @param  string  $classPath
     * @param  string  $correctNamespace
     */
    protected static function errorOut(string $classPath, string $correctNamespace)
    {
        app(ErrorPrinter::class)->print(' - Incorrect namespace');
        app(ErrorPrinter::class)->print($classPath);
        app(ErrorPrinter::class)->print('It should be:   namespace '.$correctNamespace.';  ');
    }
}
