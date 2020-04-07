<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\LaravelMicroscope\Commands\CheckView;
use Imanghafoori\LaravelMicroscope\View\ModelParser;
use ReflectionClass;

class ModelRelations
{
    /**
     * @param  string  $class
     * @param  ReflectionClass  $ref
     */
    public static function checkModelsRelations(string $class, ReflectionClass $ref)
    {
        if (! is_subclass_of($class, Model::class)) {
            return;
        }

        foreach (CheckView::get_class_methods($ref) as $method) {
            $params = (new ModelParser())->retrieveFromMethod($method, $ref);
            foreach ($params as $param) {
                if (! class_exists($param[0])) {
                    app(ErrorPrinter::class)->badRelation($ref, $method, $param);
                }
            }
        }
    }
}
