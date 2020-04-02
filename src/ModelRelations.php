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
            $params = (new ModelParser())->retrieveFromMethod($method, $ref);

            foreach($params as $p) {
                if (! class_exists($p[0])) {
                    app(ErrorPrinter::class)->badRelation($ref, $method, $p);
                }
            }
        }
    }
}
