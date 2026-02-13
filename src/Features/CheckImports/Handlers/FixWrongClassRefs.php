<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Analyzers\Fixer;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use JetBrains\PhpStorm\Pure;

/**
 * @codeCoverageIgnore
 */
class FixWrongClassRefs
{
    public static function handle(array $wrongClassRefs, PhpFileDescriptor $file, $hostNamespace, array $tokens): array
    {
        $printer = ErrorPrinter::singleton();

        foreach ($wrongClassRefs as $classReference) {
            $wrongClassRef = $classReference['class'];
            $line = $classReference['line'];

            if (! Fixer::isInUserSpace($wrongClassRef)) {
                self::wrongRef($printer, $wrongClassRef, $file, $line);
                continue;
            }

            $beforeFix = $file->getContent();
            [, $corrections] = self::fixClassReference($file, $wrongClassRef, $line, $hostNamespace);
            // To make sure that the file is really changed,
            // and we do not end up in an infinite loop.
            $afterFix = $file->getContent();
            $isFixed = $beforeFix !== $afterFix;

            // print
            if ($isFixed) {
                self::printFixation($file, $wrongClassRef, $line, $corrections);
            } else {
                self::wrongUsedClassError($file, $wrongClassRef, $line);
            }

            if ($isFixed) {
                $tokens = token_get_all($afterFix);

                return [$tokens, true];
            }
        }

        return [$tokens, false];
    }

    private static function fixClassReference($file, $class, $line, $namespace)
    {
        $baseClassName = self::removeFirst($namespace.'\\', $class);

        // Imports the correct namespace:
        [$wasCorrected, $corrections] = Fixer::fixReference($file, $baseClassName, $line);

        if ($wasCorrected) {
            return [$wasCorrected, $corrections];
        }

        return Fixer::fixReference($file, $class, $line);
    }

    private static function wrongRef($printer, $wrongClassRef, $file, int $line): void
    {
        $wrongClassRef = Color::yellow(class_basename($wrongClassRef));
        $printer->simplePendError(
            self::getLineContent($line, $file),
            $file,
            $line,
            'wrongClassRef',
            "Inline class Ref '$wrongClassRef' does not exist:"
        );
    }

    private static function printFixation(PhpFileDescriptor $file, $wrongClass, int $line, $correct)
    {
        ErrorPrinter::singleton()->simplePendError(
            'Fixed to:   '.substr($correct[0], 0, 55),
            $file,
            $line,
            'ns_replacement',
            Color::yellow($wrongClass).'  <=== Did not exist'
        );
    }

    private static function wrongUsedClassError(PhpFileDescriptor $file, $class, int $line)
    {
        ErrorPrinter::singleton()->simplePendError(
            self::getLineContent($line, $file),
            $file,
            $line,
            'wrongClassRef',
            'Class '.Color::yellow(class_basename($class)).' does not exist:'
        );
    }

    #[Pure]
    private static function removeFirst($search, $subject)
    {
        if (($position = strpos($subject, $search)) !== false) {
            return substr_replace($subject, '', $position, strlen($search));
        }

        return $subject;
    }

    private static function getLineContent(int $line, $file): string
    {
        return Color::gray("$line| ").trim($file->getLine($line), PHP_EOL.' ');
    }
}
