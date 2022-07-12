<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Imanghafoori\TokenAnalyzer\ClassMethods;
use Imanghafoori\TokenAnalyzer\Refactor;

class ActionsComments
{
    public static $command;

    public static $controllers = [];

    public static function check($tokens, $absFilePath, $classFilePath, $psr4Path, $psr4Namespace)
    {
        self::checkControllerActionsForRoutes($classFilePath, $psr4Path, $psr4Namespace, $tokens);
    }

    public static function checkControllerActionsForRoutes($classFilePath, $psr4Path, $psr4Namespace, $tokens)
    {
        $fullNamespace = RoutelessActions::getFullNamespace($classFilePath, $psr4Path, $psr4Namespace);

        if (isset(static::$controllers[trim($fullNamespace, '\\')])) {
            self::checkActions($tokens, $fullNamespace, $classFilePath);
        }
    }

    protected static function checkActions($tokens, $fullNamespace, $path)
    {
        $methods = ClassMethods::read($tokens)['methods'];

        $methods = RoutelessActions::getControllerActions($methods);
        $routelessActions = [];
        $shouldSave = false;
        $allRoutes = app('router')->getRoutes()->getRoutes();

        foreach ($methods as $method) {
            $classAtMethod = RoutelessActions::classAtMethod($fullNamespace, $method['name'][1]);
            $actions = [];
            foreach ($allRoutes as $route) {
                $action = $route->getAction('uses');
                $classAtMethod === $action && $actions[] = $route;
            }

            if (! $actions) {
                continue;
            }

            /**
             * @var $route \Illuminate\Routing\Route
             */
            $msg = '/**';
            $separator = "\n         *";

            foreach ($actions as $i => $action) {
                $i === count($actions) - 1 && $separator = '';
                $msg .= "\n         ".rtrim(self::getMsg($action)).$separator;
            }

            $msg .= "\n         */";
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
        if ($shouldSave && (self::$command)->confirm($question, true)) {
            Refactor::saveTokens($path->getRealpath(), $tokens);
        }

        return $routelessActions;
    }

    protected static function getMsg($route)
    {
        $methods = $route->methods();
        ($methods == ['GET', 'HEAD']) && $methods = ['GET'];

        $routeName = $route->getName() ?: '';
        $middlewares = self::gatherMiddlewares($route);
        [$file, $line] = self::getCallsiteInfo($methods[0], $route);
        $url = $route->uri();

        if (($url[0] ?? '') !== '/') {
            $url = '/'.$url;
        }

        $viewData = [
            'middlewares' => $middlewares,
            'routeName' => $routeName,
            'file' => $file,
            'line' => $line,
            'methods' => $methods,
            'url' => $url,
        ];

        if (view()->exists('vendor.microscope.actions_comment')) {
            $viewFile = 'vendor.microscope.actions_comment';
        } else {
            $viewFile = config('microscope.action_comment_template', 'microscope_package::actions_comment');
            if (! view()->exists('vendor.microscope.actions_comment')) {
                $viewFile = 'microscope_package::actions_comment';
            }
        }

        return view($viewFile, $viewData)->render();
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

    private static function gatherMiddlewares($route)
    {
        $middlewares = $route->gatherMiddleware();

        foreach ($middlewares as $i => $m) {
            if (! is_string($m)) {
                $middlewares[$i] = 'Closure';
            }
        }

        return $middlewares;
    }
}
