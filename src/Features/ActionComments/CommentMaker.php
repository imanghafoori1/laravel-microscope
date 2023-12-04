<?php

namespace Imanghafoori\LaravelMicroscope\Features\ActionComments;

class CommentMaker
{
    public static function getComment(array $actions)
    {
        $msg = '/**';
        $separator = "\n         *";

        foreach ($actions as $i => $action) {
            $i === count($actions) - 1 && $separator = '';
            $msg .= "\n         ".rtrim(self::getMsg($action)).$separator;
        }

        $msg .= "\n         */";

        return $msg;
    }

    private static function getMsg($route)
    {
        $methods = $route->methods();
        ($methods == ['GET', 'HEAD']) && $methods = ['GET'];

        $routeName = $route->getName() ?: '';
        $middlewares = self::gatherMiddlewares($route);
        [$path, $line] = ActionsComments::getCallsiteInfo($methods[0], $route);
        $url = self::getUrl($route);

        $viewFile = self::getViewFileName();

        return view($viewFile, [
            'middlewares' => $middlewares,
            'routeName' => $routeName,
            'file' => $path,
            'line' => $line,
            'methods' => $methods,
            'url' => $url,
        ])->render();
    }

    private static function getViewFileName()
    {
        if (view()->exists('vendor.microscope.actions_comment')) {
            $viewFile = 'vendor.microscope.actions_comment';
        } else {
            $viewFile = config('microscope.action_comment_template', 'microscope_package::actions_comment');
            if (! view()->exists('vendor.microscope.actions_comment')) {
                $viewFile = 'microscope_package::actions_comment';
            }
        }

        return $viewFile;
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

    private static function getUrl($route)
    {
        $url = $route->uri();

        if (($url[0] ?? '') !== '/') {
            $url = '/'.$url;
        }

        return $url;
    }
}
