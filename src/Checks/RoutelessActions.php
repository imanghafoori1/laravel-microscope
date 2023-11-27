<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Routing\Controller;
use ImanGhafoori\ComposerJson\NamespaceCalculator;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\TokenAnalyzer\ClassMethods;
use Imanghafoori\TokenAnalyzer\Str;
use Throwable;

class RoutelessActions
{
    public static function check($tokens, $absFilePath, $params, $classFilePath, $psr4Path, $psr4Namespace)
    {
        self::checkControllerActionsForRoutes($classFilePath, $psr4Path, $psr4Namespace, $tokens, $absFilePath);
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

    public static function getNamespacedClassName($classFilePath, $psr4Path, $psr4Namespace)
    {
        $absFilePath = $classFilePath->getRealPath();
        $className = $classFilePath->getFilename();
        $relativePath = \str_replace(base_path(), '', $absFilePath);
        $namespace = NamespaceCalculator::calculateCorrectNamespace($relativePath, $psr4Path, $psr4Namespace);

        return $namespace.'\\'.$className;
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

    public static function getFullNamespace($classFilePath, $psr4Path, $psr4Namespace)
    {
        $fullNamespace = self::getNamespacedClassName($classFilePath, $psr4Path, $psr4Namespace);

        return Str::replaceFirst('.php', '', $fullNamespace);
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
            if (
                ! self::getByAction($classAtMethod) || ($method['name'][1] === '__invoke' && ! self::getByAction($classAtMethod.'@__invoke'))
            ) {
                $line = $method['name'][2];
                $actions[] = [$line, $classAtMethod];
            }
        }

        return $actions;
    }

    public static function checkControllerActionsForRoutes($classFilePath, $psr4Path, $psr4Namespace, $tokens, $absFilePath)
    {
        $fullNamespace = self::getFullNamespace($classFilePath, $psr4Path, $psr4Namespace);

        if (! self::isLaravelController($fullNamespace)) {
            return;
        }

        // exclude abstract class
        if ((new \ReflectionClass($fullNamespace))->isAbstract()) {
            return;
        }

        $actions = self::findOrphanActions($tokens, $fullNamespace);

        self::printErrors($actions, $absFilePath);
    }

    public static function classAtMethod($fullNamespace, $methodName)
    {
        ($methodName == '__invoke') ? ($methodName = '') : ($methodName = '@'.$methodName);

        return \trim($fullNamespace, '\\').$methodName;
    }

    protected static function getByAction($classAtMethod)
    {
        return app('router')->getRoutes()->getByAction($classAtMethod);
    }

    private static function printErrors(array $actions, $absFilePath): void
    {
        $errorPrinter = ErrorPrinter::singleton();

        foreach ($actions as $action) {
            $errorPrinter->simplePendError($action[1], $absFilePath, $action[0], 'routelessCtrl', 'No route is defined for controller action:');
        }
    }
}
