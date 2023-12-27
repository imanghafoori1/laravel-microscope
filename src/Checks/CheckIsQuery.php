<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class CheckIsQuery
{
    public static function check($tokens, $absPath)
    {
        [$classes] = ParseUseStatement::findClassReferences($tokens);

        foreach ($classes as $class) {
            $c = $class['class'];
            if (self::isQueryClass($c)) {
                self::queryInBlade($absPath, $class['class'], $class['line']);
            }
        }
    }

    private static function isQueryClass($class)
    {
        $queryBuilder = ['\\'.DB::class, DB::class, '\DB', 'DB'];

        return is_subclass_of($class, Model::class) || \in_array($class, $queryBuilder);
    }

    private static function queryInBlade($absPath, $class, $lineNumber)
    {
        $key = 'queryInBlade';
        $header = 'Query in blade file: ';
        $p = ErrorPrinter::singleton();
        $errorData = $p->color($class).'  <=== DB query in blade file';
        $p->addPendingError($absPath, $lineNumber, $key, $header, $errorData);
    }
}
