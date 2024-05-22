<?php

namespace Imanghafoori\LaravelMicroscope\Features\ActionComments;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Features\CheckDeadControllers\RoutelessControllerActions;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\ClassMethods;
use Imanghafoori\TokenAnalyzer\Refactor;

class ActionsComments implements Check
{
    public static $command;

    public static $controllers = [];

    public static function check(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();
        $absFilePath = $file->getAbsolutePath();

        $fullNamespace = $file->getNamespace();

        if (isset(static::$controllers[trim($fullNamespace, '\\')])) {
            self::checkActions($tokens, $fullNamespace, $absFilePath);
        }
    }

    private static function checkActions($tokens, $fullNamespace, $absFilePath)
    {
        $methods = ClassMethods::read($tokens)['methods'];

        $methods = RoutelessControllerActions::getControllerActions($methods);
        $shouldSave = false;
        $allRoutes = app('router')->getRoutes()->getRoutes();

        foreach ($methods as $method) {
            $classAtMethod = RoutelessControllerActions::classAtMethod($fullNamespace, $method['name'][1]);
            $routes = self::getActionRoutes($allRoutes, $classAtMethod);

            if (! $routes) {
                continue;
            }

            /**
             * @var $route \Illuminate\Routing\Route
             */
            $msg = CommentMaker::getComment($routes);
            $commentIndex = $method['startBodyIndex'][0] + 1;

            if (T_DOC_COMMENT !== $tokens[$commentIndex + 1][0]) {
                // in case there is no doc-block
                $shouldSave = true;
                $tokens[$commentIndex][1] = "\n        ".$msg.$tokens[$commentIndex][1];
            } elseif ($msg !== $tokens[$commentIndex + 1][1]) {
                // if the docblock is there, but needs update...
                $shouldSave = true;
                $tokens[$commentIndex + 1][1] = $msg;
            }
        }

        $question = 'Add route definition into the: <fg=yellow>'.$fullNamespace.'</>';
        if ($shouldSave && self::$command->confirm($question, true)) {
            Refactor::saveTokens($absFilePath, $tokens);
        }
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

    private static function getActionRoutes($allRoutes, $method)
    {
        $routes = [];
        foreach ($allRoutes as $route) {
            $method === $route->getAction('uses') && ($routes[] = $route);
        }

        return $routes;
    }
}
