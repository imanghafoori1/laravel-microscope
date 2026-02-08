<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckBladeQueries;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class IsQueryCheck implements Check
{
    public static function check(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();
        [$classes] = ParseUseStatement::findClassReferences($tokens);

        Loop::mapIf(
            $classes,
            fn ($class) => self::isQueryClass($class['class']),
            fn ($class) => self::queryInBlade($file, $class['class'], $class['line'])
        );
    }

    private static function isQueryClass($class)
    {
        $queryBuilder = ['\\'.DB::class, DB::class, '\DB', 'DB'];

        return is_subclass_of($class, Model::class) || in_array($class, $queryBuilder, true);
    }

    private static function queryInBlade(PhpFileDescriptor $file, $class, $lineNumber)
    {
        $key = 'queryInBlade';
        $header = 'Query in blade file: ';
        $p = ErrorPrinter::singleton();
        $errorData = Color::blue($class).'  <=== DB query in blade file';
        $p->addPendingError($file->getAbsolutePath(), $lineNumber, $key, $header, $errorData);
    }
}
