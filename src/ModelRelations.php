<?php

namespace Imanghafoori\LaravelMicroscope;

use Imanghafoori\LaravelMicroscope\Analyzers\ParseUseStatement;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\View\MethodParser;

class ModelRelations
{
    public static $relations = [
        'hasOne',
        'hasMany',
        'belongsTo',
        'belongsToMany',
        'belongsToOne',
        'hasManyThrough',
        // This can work even with no parameter, so we ignore it.
        // 'morphTo',
        'morphToMany',
        'morphedByMany',
    ];

    public static function checkModelRelations(array $tokens, $currentNamespace, $class, $absFilePath)
    {
        $relations = MethodParser::extractParametersValueWithinMethod($tokens, self::$relations);
        $p = app(ErrorPrinter::class);
        foreach ($relations as $relation) {
            // check parameters
            foreach ($relation['params'] as $param) {
                if ($param) {
                    $uses = ParseUseStatement::getUseStatementsByPath($currentNamespace.'\\'.$class);
                    $param = $uses[$param][0] ?? $param;
                    if (in_array($param[0], ["'", '"']) && ! class_exists(trim($param, '\'\"'))) {
                        $p->badRelation($absFilePath, $relation['line'], $param);
                    }
                } else {
                    // todo warn if there was no parameter passed.
                }
                // todo check the rest of the parameters if needed for some types of relations.
                break;
            }
            // check has return
            if (! $relation['hasReturn']) {
                // todo print error that the relation should have return
            }
        }
    }
}
