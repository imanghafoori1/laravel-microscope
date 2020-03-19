<?php
namespace Imanghafoori\LaravelSelfTest;

use Illuminate\Events\Dispatcher;
use Illuminate\Contracts\Container\BindingResolutionException;

class CheckerDispatcher extends Dispatcher
{
    public function listen($events, $listener)
    {
        $event = (array)$events;
        $this->validateCallback($event[0], $listener);
    }

    private function error(string $string)
    {
        app(ErrorPrinter::class)->print($string);
    }

    private function isLikeClassPath($event)
    {
        return count(explode('\\', $event)) > 1;
    }
    /**
     * @param $listener
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function validateCallback($event, $listener)
    {
        if ($this->isLikeClassPath($event) && ! (class_exists($event) || interface_exists($event))) {
            return $this->error("The Event class: \"$event\" you are listening to does not exist.");
        }

        if (! is_string($listener)) {
            return;
        }

        [
            $class,
            $method,
        ] = $this->parseClassCallable($listener);

        try {
            $obj = app()->make($class);
        } catch (BindingResolutionException $e) {
            return $this->error($this->noClass($event, $class, $method));
        }

        if (! method_exists($obj, $method)) {
            return $this->error($this->noMethod($event, $class, $method));
        }

        $typeHintClassPath = null;
        $typeHint = (new \ReflectionParameter([
            $obj,
            $method,
        ], 0))->getType();
        if ($typeHint) {
            $typeHintClassPath = $typeHint->getName();

            if (! (class_exists($typeHintClassPath) || interface_exists($typeHintClassPath))) {
                return $this->error('The type hint is wrong on the listener: '.$typeHintClassPath);
            }
        }

        $eventName = $this->stringify($event);

        if (class_exists($eventName)) {
            if ($typeHintClassPath && !($eventName == $typeHintClassPath || is_subclass_of($eventName, $typeHintClassPath))) {
                return $this->error('The type hint on the listener: '.$listener.' does not match the event class path.');
            }
        }
    }

    private function stringify($event)
    {
        return is_object($event) ? get_class($event) : $event;
    }

    protected function noClass($event, $class, $method)
    {
        $at = implode('@', [
            $class,
            $method,
        ]);

        $e = $this->stringify($event);

        return 'The class of '.$at.' can not be resolved as a listener for "'.$e.'" event';
    }

    protected function noMethod($event, $class, $method)
    {
        $at = implode('@', [
            $class,
            $method,
        ]);
        $e = $this->stringify($event);

        return 'The method of '.$at.' is not callable as an event listener for "'.$e.'" event';
    }
}
