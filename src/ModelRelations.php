<?php

namespace Imanghafoori\LaravelSelfTest;

use ReflectionClass;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\LaravelSelfTest\View\ModelParser;

class ModelRelations
{
    /**
     * @param  string  $class
     * @param  ReflectionClass  $ref
     */
    static function checkModelsRelations(string $class, ReflectionClass $ref)
    {
        if (! is_subclass_of($class, Model::class)) {
            return;
        }

        foreach ($ref->getMethods() as $method) {
            $errors = (new ModelParser())->retrieveFromMethod($method);
            foreach ($errors as $err) {
                self::error($err);
            }
        }
    }

    /**
     * @param $err
     */
    protected static function error($err)
    {
        app(ErrorPrinter::class)->print('- Wrong model is passed in relation');
        app(ErrorPrinter::class)->print($err['file']);
        app(ErrorPrinter::class)->print('line: '.$err['lineNumber'].'       '.trim($err['line']));
        app(ErrorPrinter::class)->print($err['name'].' is not a valid class.');
        app(ErrorPrinter::class)->print('/********************************************/');
    }
}
