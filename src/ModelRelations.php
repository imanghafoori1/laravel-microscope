<?php

namespace Imanghafoori\LaravelSelfTest;

use ReflectionClass;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\LaravelSelfTest\View\ModelParser;
use Imanghafoori\LaravelSelfTest\Commands\CheckView;

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

        foreach (CheckView::get_class_methods($ref) as $method) {
            $params = (new ModelParser())->retrieveFromMethod($method, $ref);
            foreach($params as $p) {
                if (! class_exists($p[0])) {
                    app(ErrorPrinter::class)->badRelation($ref, $method, $p);
                }
            }
        }
    }
}
