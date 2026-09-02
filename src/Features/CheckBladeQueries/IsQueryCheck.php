<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckBladeQueries;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Imanghafoori\LaravelMicroscope\Check;
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
            static fn ($class) => self::isQuery($class['class']),
            static fn ($class) => BladeQueryHandler::handle($file, $class['class'], $class['line'])
        );
    }

    private static function isQuery($class)
    {
        $queryBuilder = ['\\'.DB::class, DB::class, '\DB', 'DB'];

        return is_subclass_of($class, Model::class) || in_array($class, $queryBuilder, true);
    }
}
