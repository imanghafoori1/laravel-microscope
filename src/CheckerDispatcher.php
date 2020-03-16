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
        dump($string);
    }

    /**
     * @param $listener
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function validateCallback($event, $listener)
    {
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
