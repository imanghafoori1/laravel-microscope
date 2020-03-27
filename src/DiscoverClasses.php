<?php

namespace Imanghafoori\LaravelSelfTest;

use SplFileInfo;
use ReflectionClass;
use ReflectionException;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class DiscoverClasses
{
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
        static::getListenerEvents((new Finder)->files()->in(base_path($path)), base_path(), $path, $namespace);
    }

    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $classes
     * @param  string  $basePath
     *
     * @param $path
     * @param $root_namespace
     *
     * @return void
     */
    protected static function getListenerEvents($classes, $basePath, $path, $root_namespace)
    {
        foreach ($classes as $classFilePath) {
            try {
                $t = static::classFromFile($classFilePath, $basePath);
                if (self::hasOpeningTag($classFilePath->getRealPath())) {
                    new ReflectionClass($t);
                }
            } catch (ReflectionException $e) {
                [
                    $incorrect_namespace,
                    $class,
                    $type,
                ] = self::getClass($classFilePath->getRealPath());

                if (! $class) {
                    continue;
                }

                $classPath = trim(Str::replaceFirst($basePath, '', $classFilePath->getRealPath()), DIRECTORY_SEPARATOR);

                $p = explode(DIRECTORY_SEPARATOR, $classPath);
                array_pop($p);
                $p = implode('\\', $p);

                app(ErrorPrinter::class)->print('Incorrect namespace at: '.$classPath);
                app(ErrorPrinter::class)->print('It should be:   namespace '.str_replace(trim($path, '\\//'), trim($root_namespace, '\\/'), $p).';    ');
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
}
