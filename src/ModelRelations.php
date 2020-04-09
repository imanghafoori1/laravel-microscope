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
        $p = app(ErrorPrinter::class);
        foreach (CheckView::get_class_methods($ref) as $method) {
            $params = (new ModelParser())->retrieveFromMethod($method, $ref);
            foreach ($params as $param) {
                $model = trim($param[0], '\'\"');
                if (! class_exists($model)) {
                    $p->badRelation($ref, $method, $model);
                }
            }
        }
    }
}
