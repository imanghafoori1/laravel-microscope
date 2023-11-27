<?php

namespace Imanghafoori\LaravelMicroscope\Features\ActionComments;

use Imanghafoori\LaravelMicroscope\Checks\RoutelessActions;
use Imanghafoori\TokenAnalyzer\ClassMethods;
use Imanghafoori\TokenAnalyzer\Refactor;

class ActionsComments
{
    public static $command;

    public static $controllers = [];

    public static function check($tokens, $absFilePath, $params, $classFilePath, $psr4Path, $psr4Namespace)
    {
        $fullNamespace = RoutelessActions::getFullNamespace($classFilePath, $psr4Path, $psr4Namespace);

        if (isset(static::$controllers[trim($fullNamespace, '\\')])) {
            self::checkActions($tokens, $fullNamespace, $classFilePath);
        }
    }

    private static function checkActions($tokens, $fullNamespace, $path)
    {
        $methods = ClassMethods::read($tokens)['methods'];

        $methods = RoutelessActions::getControllerActions($methods);
        $routelessActions = [];
        $shouldSave = false;
        $allRoutes = app('router')->getRoutes()->getRoutes();

        foreach ($methods as $method) {
            $classAtMethod = RoutelessActions::classAtMethod($fullNamespace, $method['name'][1]);
            $actions = self::getActions($allRoutes, $classAtMethod);

            if (! $actions) {
                continue;
            }

            /**
             * @var $route \Illuminate\Routing\Route
             */
            $msg = CommentMaker::getComment($actions);
            $commentIndex = $method['startBodyIndex'][0] + 1;

            if (T_DOC_COMMENT !== $tokens[$commentIndex + 1][0]) {
                $shouldSave = true;
                $tokens[$commentIndex][1] = "\n        ".$msg.$tokens[$commentIndex][1];
            } elseif ($msg !== $tokens[$commentIndex + 1][1]) {
                // if the docblock is there, but needs update...
                $shouldSave = true;
                $tokens[$commentIndex + 1][1] = $msg;
            }

            $line = $method['name'][2];
            $routelessActions[] = [$line, $classAtMethod];
        }

        $question = 'Add route definition into the: <fg=yellow>'.$fullNamespace.'</>';
        if ($shouldSave && self::$command->confirm($question, true)) {
            Refactor::saveTokens($path->getRealpath(), $tokens);
        }

        return $routelessActions;
    }

    public static function getCallsiteInfo($methods, $route)
    {
        $callsite = app('router')->getRoutes()->routesInfo[$methods][$route->uri()] ?? [];
        $file = $callsite[0]['file'] ?? '';
        $line = $callsite[0]['line'] ?? '';
        $file = \trim(str_replace(base_path(), '', $file), '\\/');
        $file = str_replace('\\', '/', $file);

        return [$file, $line];
    }

    private static function getActions($allRoutes, $classAtMethod)
    {
        $actions = [];
        foreach ($allRoutes as $route) {
            $action = $route->getAction('uses');
            $classAtMethod === $action && $actions[] = $route;
        }

        return $actions;
    }
}
