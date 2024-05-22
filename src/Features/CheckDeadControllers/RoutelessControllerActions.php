<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckDeadControllers;

use Illuminate\Routing\Controller;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\ClassMethods;
use ReflectionClass;
use Throwable;

class RoutelessControllerActions implements Check
{
    public static function check(PhpFileDescriptor $file)
    {
        $fullNamespace = $file->getNamespace();

        if (! self::isLaravelController($fullNamespace)) {
            return;
        }

        // exclude abstract class
        if ((new ReflectionClass($fullNamespace))->isAbstract()) {
            return;
        }

        $actions = self::findOrphanActions($file->getTokens(), $fullNamespace);

        self::printErrors($actions, $file->getAbsolutePath());
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

            $methodName = $method['name'][1];
            // we exclude __construct
            if ($methodName == '__construct') {
                continue;
            }

            $orphanMethods[] = $method;
        }

        return $orphanMethods;
    }

    public static function isLaravelController($fullNamespace)
    {
        try {
            return is_subclass_of($fullNamespace, Controller::class);
        } catch (Throwable $r) {
            // it means the file does not contain a class or interface.
            return false;
        }
    }

    protected static function findOrphanActions($tokens, $fullNamespace)
    {
        $class = ClassMethods::read($tokens);

        $methods = self::getControllerActions($class['methods']);
        $actions = [];
        foreach ($methods as $method) {
            $classAtMethod = self::classAtMethod($fullNamespace, $method['name'][1]);
            // For __invoke, we will also check to see if the route is defined like this:
            // Route::get('/', [Controller::class, '__invoke']);
            // Route::get('/', Controller::class);
            if (! self::getByAction($classAtMethod)) {
                ! strpos($classAtMethod, '@') && $classAtMethod .= '@__invoke';
                $line = $method['name'][2];
                $actions[] = [$line, $classAtMethod];
            }
        }

        return $actions;
    }

    public static function classAtMethod($fullNamespace, $methodName)
    {
        $methodName = $methodName === '__invoke' ? '' : '@'.$methodName;

        return trim($fullNamespace, '\\').$methodName;
    }

    protected static function getByAction($classAtMethod)
    {
        return app('router')->getRoutes()->getByAction($classAtMethod);
    }

    private static function printErrors(array $actions, $absFilePath)
    {
        $errorPrinter = ErrorPrinter::singleton();

        foreach ($actions as $action) {
            $errorPrinter->simplePendError($action[1], $absFilePath, $action[0], 'routelessCtrl', 'No route is defined for controller action:');
        }
    }
}
