<?php

namespace Imanghafoori\LaravelMicroscope\Handlers;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\Fixer;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class WrongClassRefs
{
    public static function handle(array $wrongClassRefs, $absFilePath, $hostNamespace, array $tokens): array
    {
        $printer = app(ErrorPrinter::class);

        foreach ($wrongClassRefs as $classReference) {
            $wrongClassRef = $classReference['class'];
            $line = $classReference['line'];

            if (! Fixer::isInUserSpace($wrongClassRef)) {
                $printer->doesNotExist($wrongClassRef, $absFilePath, $line, 'wrongReference', 'Inline class Ref does not exist:');
                continue;
            }

            $beforeFix = file_get_contents($absFilePath);
            [, $corrections] = self::fixClassReference($absFilePath, $wrongClassRef, $line, $hostNamespace);
            // To make sure that the file is really changed,
            // and we do not end up in an infinite loop.
            $afterFix = file_get_contents($absFilePath);
            $isFixed = $beforeFix !== $afterFix;

            // print
            $method = $isFixed ? 'printFixation' : 'wrongImportPossibleFixes';
            $printer->$method($absFilePath, $wrongClassRef, $line, $corrections);

            if ($isFixed) {
                $tokens = token_get_all($afterFix);

                return [$tokens, $isFixed];
            }
        }

        return [$tokens, false];
    }

    private static function fixClassReference($absFilePath, $class, $line, $namespace)
    {
        $baseClassName = Str::replaceFirst($namespace.'\\', '', $class);

        // Imports the correct namespace:
        [$wasCorrected, $corrections] = Fixer::fixReference($absFilePath, $baseClassName, $line);

        if ($wasCorrected) {
            return [$wasCorrected, $corrections];
        }

        return Fixer::fixReference($absFilePath, $class, $line);
    }
}
