<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Routing\Controller;
use Imanghafoori\LaravelMicroscope\Analyzers\ClassMethods;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class RoutelessActions
{
    public static function getControllerActions($methods)
    {
        $orphanMethods = [];
        foreach ($methods as $method) {
            // we exclude non-public methods
            if ($method['visibility'][0] !== T_PUBLIC) {
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
        $namespace = NamespaceCorrector::calculateCorrectNamespace($relativePath, $psr4Path, $psr4Namespace);

        return $namespace.'\\'.$className;
    }

    public static function isLaravelController($fullNamespace)
    {
        try {
            return is_subclass_of($fullNamespace, Controller::class);
        } catch (\Throwable $r) {
            // it means the file does not contain a class or interface.
            return false;
        }
    }

    public static function getFullNamespace($classFilePath, $psr4Path, $psr4Namespace)
    {
        $fullNamespace = self::getNamespacedClassName($classFilePath, $psr4Path, $psr4Namespace);

        return \trim($fullNamespace, '.php');
    }

    protected function findOrphanActions($tokens, $fullNamespace)
    {
        $class = ClassMethods::read($tokens);

        $methods = self::getControllerActions($class['methods']);
        $routelessActions = [];
        foreach ($methods as $method) {
            $classAtMethod = self::classAtMethod($fullNamespace, $method['name'][1]);

            if (! app('router')->getRoutes()->getByAction($classAtMethod)) {
                $line = $method['name'][2];
                $routelessActions[] = [$line, $classAtMethod];
            }
        }

        return $routelessActions;
    }

    public static function check($tokens, $absFilePath, $classFilePath, $psr4Path, $psr4Namespace)
    {
        (new self())->checkControllerActionsForRoutes($classFilePath, $psr4Path, $psr4Namespace, $tokens, $absFilePath);
    }

    public function checkControllerActionsForRoutes($classFilePath, $psr4Path, $psr4Namespace, $tokens, $absFilePath)
    {
        $errorPrinter = resolve(ErrorPrinter::class);
        $fullNamespace = self::getFullNamespace($classFilePath, $psr4Path, $psr4Namespace);

        if (! self::isLaravelController($fullNamespace)) {
            return;
        }

        // exclude abstract class
        if ((new \ReflectionClass($fullNamespace))->isAbstract()) {
            return;
        }

        $actions = $this->findOrphanActions($tokens, $fullNamespace);

        foreach ($actions as $action) {
            $errorPrinter->routelessAction($absFilePath, $action[0], $action[1]);
        }
    }

    public static function classAtMethod($fullNamespace, $methodName)
    {
        ($methodName == '__invoke') ? ($methodName = '') : ($methodName = '@'.$methodName);

        return \trim($fullNamespace, '\\').$methodName;
    }
}
