<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDeadControllers;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\SearchReplace\Searcher;
use Imanghafoori\TokenAnalyzer\ClassMethods;
use ReflectionClass;
use Throwable;

class RoutelessControllerActions implements Check
{
    public static $routes = Dependencies\RouteChecker::class;

    public static $baseController = 'Illuminate\\Routing\\Controller';

    public static $errors = Dependencies\DeadControllerErrors::class;

    public static function check(PhpFileDescriptor $file)
    {
        $fullNamespace = $file->getNamespace();

        if (! self::isLaravelController($fullNamespace)) {
            return;
        }

        // exclude abstract class
        if (self::isAbstract($fullNamespace)) {
            return;
        }

        $actions = self::findOrphanActions($file->getTokens(), $fullNamespace);

        (self::$errors)::printErrors($actions, $file);
    }

    public static function getControllerActions($methods)
    {
        $orphanMethods = [];
        foreach ($methods as $method) {
            // we exclude non-public methods
            if ($method['visibility'][0] !== T_PUBLIC) {
                continue;
            }

            // we exclude static methods
            if ($method['is_static']) {
                continue;
            }

            // we exclude __construct
            if ($method['name'][1] == '__construct') {
                continue;
            }

            $orphanMethods[] = $method;
        }

        return $orphanMethods;
    }

    public static function isLaravelController($fullNamespace)
    {
        try {
            return is_subclass_of($fullNamespace, self::$baseController);
        } catch (Throwable $r) {
            // it means the file does not contain a class or interface.
            return false;
        }
    }

    public static function findOrphanActions($tokens, $fullNamespace)
    {
        $class = ClassMethods::read($tokens);

        $methods = self::getControllerActions($class['methods']);

        return self::filterMethods($methods, $fullNamespace, $tokens);
    }

    public static function classAtMethod($fullNamespace, $methodName)
    {
        $methodName = $methodName === '__invoke' ? '' : '@'.$methodName;

        return trim($fullNamespace, '\\').$methodName;
    }

    protected static function hasRoute($classAtMethod)
    {
        return (self::$routes)::hasRoute($classAtMethod);
    }

    private static function hasPrivateCall($tokens, $methodName)
    {
        try {
            $result = Searcher::searchFirst([
                [
                    'search' => '$this->'.$methodName,
                    'replace' => '$this->'.$methodName,
                ],
            ], $tokens);
        } catch (Throwable $e) {
            return false;
        }

        return (bool) $result[1];
    }

    private static function isAbstract($fullNamespace): bool
    {
        return (new ReflectionClass($fullNamespace))->isAbstract();
    }

    private static function filterMethods($methods, $fullNamespace, $tokens): array
    {
        $actions = [];
        foreach ($methods as $method) {
            $classAtMethod = self::classAtMethod($fullNamespace, $method['name'][1]);
            if (self::hasRoute($classAtMethod)) {
                continue;
            }
            // For __invoke, we will also check to see if the route is defined like this:
            // Route::get('/', [Controller::class, '__invoke']);
            // Route::get('/', Controller::class);
            $line = $method['name'][2];
            if (! strpos($classAtMethod, '@')) {
                $classAtMethod .= '@__invoke';
                if (! self::hasRoute($classAtMethod)) {
                    $actions[] = [$line, $classAtMethod];
                }
            } elseif (! self::hasPrivateCall($tokens, $method['name'][1])) {
                $actions[] = [$line, $classAtMethod];
            }
        }

        return $actions;
    }
}
