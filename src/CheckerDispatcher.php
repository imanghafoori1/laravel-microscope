<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;

class CheckerDispatcher extends Dispatcher
{
    public function listen($events, $listener)
    {
        $event = (array) $events;
        $this->validateCallback($event[0], $listener);
    }

    private function error(string $string)
    {
        $p = app(ErrorPrinter::class);
        $p->others($string);
    }

    private function isLikeClassPath($event)
    {
        return (count(explode('\\', $event)) > 1) && ! Str::contains($event, [' ', '.', ':', '-', '@']);
    }

    protected function validateCallback($event, $listener)
    {
        if ($this->isLikeClassPath($event) && ! $this->exists($event)) {
            return $this->error("The Event class: \"$event\" you are listening to does not exist.");
        }

        if (! is_string($listener)) {
            return;
        }

        [
            $listenerClass,
            $methodName,
        ] = $this->parseClassCallable($listener);

        try {
            $listenerObj = app()->make($listenerClass);
        } catch (BindingResolutionException $e) {
            return $this->error($this->noClass($event, $listenerClass, $methodName));
        }

        if (! method_exists($listenerObj, $methodName)) {
            return $this->error($this->noMethod($event, $listenerClass, $methodName));
        }

        $typeHintClassPath = $this->getTypeHintedClass($listenerObj, $methodName);

        if ($typeHintClassPath && ! $this->exists($typeHintClassPath)) {
            return $this->error('The type hint is wrong on the listener: public function '.$methodName.'('.$typeHintClassPath.' $...');
        }

        $eventName = $this->stringify($event);

        if (class_exists($eventName)) {
            if ($typeHintClassPath && ! ($eventName == $typeHintClassPath || is_subclass_of($eventName, $typeHintClassPath))) {
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

    private function exists($event)
    {
        return class_exists($event) || interface_exists($event);
    }

    protected function getTypeHintedClass($listenerObj, $methodName)
    {
        $typeHint = (new \ReflectionParameter([
            $listenerObj,
            $methodName,
        ], 0))->getType();

        return $typeHint ? $typeHint->getName() : null;
    }
}
