<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Routing\Controller;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Analyzers\ClassMethods;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;

class RoutelessActions
{
    private function getControllerActions($methods)
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

    private function getNamespacedClassName($classFilePath, $psr4Path, $psr4Namespace)
    {
        $absFilePath = $classFilePath->getRealPath();
        $className = $classFilePath->getFilename();
        $relativePath = str_replace(base_path(), '', $absFilePath);
        $namespace = NamespaceCorrector::calculateCorrectNamespace($relativePath, $psr4Path, $psr4Namespace);

        return $namespace.'\\'.$className;
    }

    private function isLaravelController($fullNamespace)
    {
        try {
            return is_subclass_of($fullNamespace, Controller::class);
        } catch (\Throwable $r) {
            // it means the file does not contain a class or interface.
            return false;
        }
    }

    private function getFullNamespace($classFilePath, $psr4Path, $psr4Namespace)
    {
        $fullNamespace = $this->getNamespacedClassName($classFilePath, $psr4Path, $psr4Namespace);
        $fullNamespace = trim($fullNamespace, '.php');

        return $fullNamespace;
    }

    private function checkActions($tokens, $fullNamespace)
    {
        $class = ClassMethods::read($tokens);

        $methods = $this->getControllerActions($class['methods']);
        $routelessActions = [];
        foreach ($methods as $method) {
            $classAtMethod = $this->classAtMethod($fullNamespace, $method['name'][1]);

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
        $fullNamespace = $this->getFullNamespace($classFilePath, $psr4Path, $psr4Namespace);

        if ($this->isLaravelController($fullNamespace)) {
            $actions = $this->checkActions($tokens, $fullNamespace);

            foreach ($actions as $action) {
                $errorPrinter->routelessAction($absFilePath, $action[0], $action[1]);
            }
        }
    }

    private function classAtMethod($fullNamespace, $methodName)
    {
        ($methodName == '__invoke') ? ($methodName = '') : ($methodName = '@'.$methodName);

        return trim($fullNamespace, '\\').$methodName;
    }
}
