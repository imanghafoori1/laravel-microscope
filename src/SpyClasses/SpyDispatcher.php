<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

use ReflectionFunction;
use ReflectionException;
use Illuminate\Support\Str;
use Illuminate\Events\Dispatcher;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\PendingError;

class SpyDispatcher extends Dispatcher
{
    public $originalListeners = [];

    public $wildcardsOriginal = [];

    public function listen($events, $listener)
    {
        parent::listen($events, $listener);

        $events = (array) $events;
        foreach($events as $event) {
            $this->validateCallback($event, $listener);
        }

        // Do not move this loop into a private method or something, it breaks.
        foreach ((array)$events as $event) {
            $i = 0;
            $excludes = [
                base_path('vendor'.DIRECTORY_SEPARATOR.'laravel'),
            ];
            while (($callSite = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $i + 1)[$i]) && Str::startsWith($callSite['file'] ?? '', $excludes)) {
                $i++;
            }
            unset($callSite['object']);
            if ($listener instanceof \Closure) {
                $listener = $this->stringifyClosure($listener);
            }

            $this->addOriginalListener([$listener, $callSite], $event);
        }
    }

    public function getOriginalListeners($eventName)
    {
        $listeners = $this->originalListeners[$eventName] ?? [];

        $wildcards = [];
        foreach ($this->wildcardsOriginal as $key => $wildcardListeners) {
            if (Str::is($key, $eventName)) {
                $wildcards = array_merge($wildcards, $wildcardListeners);
            }
        }

        $listeners = array_merge($listeners, $wildcards);

        return class_exists($eventName, false) ? $this->addOriginInterfaceListeners($eventName, $listeners) : $listeners;
    }

    private function error($string)
    {
        $len = strlen($string);
        PendingError::$maxLength < $len && PendingError::$maxLength = $len;
        app(ErrorPrinter::class)->pended[] = $string;
    }

    protected function validateCallback($event, $listener)
    {
        if (! is_string($listener)) {
            return;
        }

        [$listenerClass, $methodName] = $this->parseClassCallable($listener);

        try {
            $listenerObj = app()->make($listenerClass);
        } catch (\Exception $e) {
            return $this->error($this->noClass($event, $listenerClass, $methodName));
        }

        if (! method_exists($listenerObj, $methodName)) {
            return $this->error($this->noMethod($event, $listenerClass, $methodName));
        }

        $typeHintClassPath = $this->getTypeHintedClass($listenerObj, $methodName);

        $eventName = $this->stringify($event);

        if (class_exists($eventName) && $typeHintClassPath) {
            if ($eventName !== $typeHintClassPath && ! is_subclass_of($eventName, $typeHintClassPath)) {
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
        $at = implode('@', [$class, $method]);
        $e = $this->stringify($event);

        return 'The class of '.$at.' can not be resolved as a listener for "'.$e.'" event';
    }

    protected function noMethod($event, $class, $method)
    {
        $at = implode('@', [$class, $method]);
        $e = $this->stringify($event);

        return 'The method of '.$at.' is not callable as an event listener for "'.$e.'" event';
    }

    protected function getTypeHintedClass($listenerObj, $methodName)
    {
        try {
            $ref = new \ReflectionParameter([$listenerObj, $methodName], 0);
            $typeHint = $ref->getType();

            return $typeHint ? $typeHint->getName() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function addOriginInterfaceListeners($eventName, $listeners)
    {
        foreach (class_implements($eventName) as $interface) {
            if (isset($this->originalListeners[$interface])) {
                foreach ($this->originalListeners[$interface] as $names) {
                    $listeners = array_merge($listeners, (array)$names);
                }
            }
        }

        return $listeners;
    }

    private function stringifyClosure($listener)
    {
        try {
            $reflection = new ReflectionFunction($listener);
            $line = $reflection->getStartLine();
            $path = FilePath::getRelativePath($reflection->getFileName());

            return 'Closure at: '.$path.':'.$line;
        } catch (ReflectionException $e) {
            return '';
        }
    }

    private function addOriginalListener($listener, $event)
    {
        if (Str::contains($event, '*')) {
            $this->wildcardsOriginal[$event][] = $listener;
        } else {
            $this->originalListeners[$event][] = $listener;
        }
    }
}
