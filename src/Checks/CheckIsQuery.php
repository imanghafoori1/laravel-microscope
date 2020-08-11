<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Imanghafoori\LaravelMicroscope\Analyzers\ParseUseStatement;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckIsQuery
{
    public static function check($tokens, $absPath)
    {
        $classes = ParseUseStatement::findClassReferences($tokens, $absPath);

        foreach ($classes as $class) {
            $c = $class['class'];
            if (self::isQueryClass($c)) {
                app(ErrorPrinter::class)->queryInBlade($absPath, $class['class'], $class['line']);
            }
        }
    }

    public static function isQueryClass($class)
    {
        $queryBuilder = ['\\'.DB::class, DB::class, '\DB', 'DB'];

        return is_subclass_of($class, Model::class) || \in_array($class, $queryBuilder);
    }
}
