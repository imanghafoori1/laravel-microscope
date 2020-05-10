<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Routing\Controller;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Analyzers\ClassMethods;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;

class RoutelessActions
{
    public function check($errorPrinter)
    {
        $psr4 = ComposerJson::readKey('autoload.psr-4');

        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            foreach ($files as $classFilePath) {
                $absFilePath = $classFilePath->getRealPath();
                $tokens = token_get_all(file_get_contents($absFilePath));

                $fullNamespace = $this->getFullNamespace($classFilePath, $psr4Path, $psr4Namespace);

                $this->checkControllerActionsForRoutes($errorPrinter, $fullNamespace, $tokens, $absFilePath);

                CheckRouteCalls::check($tokens, $absFilePath);
            }
        }
    }

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

    private function checkControllerActionsForRoutes($errorPrinter, $fullNamespace, $tokens, $absFilePath)
    {
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
