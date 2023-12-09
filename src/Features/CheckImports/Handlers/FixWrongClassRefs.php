<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\Analyzers\Fixer;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class FixWrongClassRefs
{
    public static function handle(array $wrongClassRefs, $absFilePath, $hostNamespace, array $tokens): array
    {
        $printer = ErrorPrinter::singleton();

        foreach ($wrongClassRefs as $classReference) {
            $wrongClassRef = $classReference['class'];
            $line = $classReference['line'];

            if (! Fixer::isInUserSpace($wrongClassRef)) {
                self::wrongRef($printer, $wrongClassRef, $absFilePath, $line);
                continue;
            }

            $beforeFix = file_get_contents($absFilePath);
            [, $corrections] = self::fixClassReference($absFilePath, $wrongClassRef, $line, $hostNamespace);
            // To make sure that the file is really changed,
            // and we do not end up in an infinite loop.
            $afterFix = file_get_contents($absFilePath);
            $isFixed = $beforeFix !== $afterFix;

            // print
            if ($isFixed) {
                self::printFixation($absFilePath, $wrongClassRef, $line, $corrections);
            } else {
                self::wrongUsedClassError($absFilePath, $wrongClassRef, $line);
            }

            if ($isFixed) {
                $tokens = token_get_all($afterFix);

                return [$tokens, true];
            }
        }

        return [$tokens, false];
    }

    private static function fixClassReference($absFilePath, $class, $line, $namespace)
    {
        $baseClassName = self::removeFirst($namespace.'\\', $class);

        // Imports the correct namespace:
        [$wasCorrected, $corrections] = Fixer::fixReference($absFilePath, $baseClassName, $line);

        if ($wasCorrected) {
            return [$wasCorrected, $corrections];
        }

        return Fixer::fixReference($absFilePath, $class, $line);
    }

    private static function wrongRef($printer, $wrongClassRef, $absFilePath, $line): void
    {
        $printer->simplePendError(
            $wrongClassRef,
            $absFilePath,
            $line,
            'wrongClassRef',
            'Inline class Ref does not exist:'
        );
    }

    private static function printFixation($absPath, $wrongClass, $lineNumber, $correct)
    {
        ErrorPrinter::singleton()->simplePendError(
            'Fixed to:   '.substr($correct[0], 0, 55),
            $absPath,
            $lineNumber,
            'ns_replacement',
            $wrongClass.'  <=== Did not exist'
        );
    }

    private static function wrongUsedClassError($absPath, $class, $line)
    {
        ErrorPrinter::singleton()->simplePendError(
            $class,
            $absPath,
            $line,
            'wrongClassRef',
            'Class does not exist:'
        );
    }

    private static function removeFirst($search, $subject)
    {
        if (($position = strpos($subject, $search)) !== false) {
            return substr_replace($subject, '', $position, strlen($search));
        }

        return $subject;
    }
}
