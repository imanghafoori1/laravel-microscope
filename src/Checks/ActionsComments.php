<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Imanghafoori\LaravelMicroscope\Analyzers\Refactor;
use Imanghafoori\LaravelMicroscope\Analyzers\ClassMethods;

class ActionsComments extends RoutelessActions
{
    public static $command;

    public static function check($tokens, $absFilePath, $classFilePath, $psr4Path, $psr4Namespace)
    {
        (new self())->checkControllerActionsForRoutes($classFilePath, $psr4Path, $psr4Namespace, $tokens, $absFilePath);
    }

    public function checkControllerActionsForRoutes($classFilePath, $psr4Path, $psr4Namespace, $tokens, $absFilePath)
    {
//        $errorPrinter = resolve(ErrorPrinter::class);
        $fullNamespace = $this->getFullNamespace($classFilePath, $psr4Path, $psr4Namespace);

        if ($this->isLaravelController($fullNamespace)) {
            $actions = $this->checkActions($tokens, $fullNamespace, $classFilePath);

            /*foreach ($actions as $action) {
                $errorPrinter->routelessAction($absFilePath, $action[0], $action[1]);
            }*/
        }
    }

    protected function checkActions($tokens, $fullNamespace, $path)
    {
        $class = ClassMethods::read($tokens);

        $methods = $this->getControllerActions($class['methods']);
        $routelessActions = [];
        $shouldSave = false;

        foreach ($methods as $method) {
            $classAtMethod = $this->classAtMethod($fullNamespace, $method['name'][1]);

            if (! ($r = app('router')->getRoutes()->getByAction($classAtMethod))) {
                continue;
            }

            /**
             * @var $r \Illuminate\Routing\Route
             */
            $methods = $r->methods();
            ($methods == ['GET', 'HEAD']) && $methods = ['GET'];
            if (count($methods) > 1) {
                $msg = '/** '."\n".'         * @methods('.implode(', ', $methods).')'."\n".'         * @uri(\'/'.$r->uri().'\')'."\n".'         * @name(\''.$r->getName().'\')'."\n         */";
            } else {
                $msg = '/** '."\n".'         * @'.strtolower(implode('', $methods)).'(\'/'.$r->uri().'\')'."\n".'         * @name(\''.$r->getName().'\')'."\n         */";
            }

            $commentIndex = $method['startBodyIndex'][0] + 1;

            if (T_DOC_COMMENT !== $tokens[$method['startBodyIndex'][0] + 2][0] || $msg !== $tokens[$method['startBodyIndex'][0] + 2][1]) {
                $shouldSave = true;
                $tokens[$commentIndex][1] = "\n        ".$msg.$tokens[$commentIndex][1];
            }

            $line = $method['name'][2];
            $routelessActions[] = [$line, $classAtMethod];
        }
        $question = 'Do you want to add route definition to: '.$fullNamespace;
        if ((self::$command)->confirm($question)) {
            $shouldSave && Refactor::saveTokens($path->getRealpath(), $tokens);
        }

        return $routelessActions;
    }
}
