<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraFQCN;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class ExtraFQCN implements Check
{
    use CachedCheck;

    /**
     * @var string
     */
    private static $cacheKey = 'extra_fqcn';

    public static $fix;

    public static $imports;

    public static $class;

    public static function performCheck(PhpFileDescriptor $file): bool
    {
        $tokens = $file->getTokens();
        $absFilePath = $file->getAbsolutePath();
        $imports = (self::$imports)($file);
        $classRefs = ImportsAnalyzer::findClassRefs($tokens, $absFilePath, $imports);
        $hasError = self::checkClassRef($classRefs, $imports, $absFilePath, self::$class, self::$fix);

        return $hasError;
    }

    private static function isDirectlyImported($class, $imports): bool
    {
        return isset($imports[self::className($class)]) && $imports[self::className($class)][0] === ltrim($class, '\\');
    }

    private static function conflictingAlias($class, $imports)
    {
        return isset($imports[self::className($class)]);
    }

    private static function isInSameNamespace($namespace, $ref)
    {
        return trim(self::beforeLast($ref, '\\'), '\\') === $namespace;
    }

    private static function beforeLast($subject, $search)
    {
        $pos = mb_strrpos($subject, $search) ?: 0;

        return mb_substr($subject, 0, $pos, 'UTF-8');
    }

    private static function checkClassRef($classRefs, $imports, $absFilePath, $class, $fix = true): bool
    {
        $hasError = false;
        $namespace = $classRefs[1];
        $imports = array_values($imports)[0];
        foreach ($classRefs[0] as $classRef) {
            if ($classRef['class'][0] !== '\\') {
                continue;
            }

            $shouldBeSkipped = $class && strpos(self::className($classRef['class']), $class) === false;
            if (self::isDirectlyImported($classRef['class'], $imports)) {
                $hasError = true;
                if (! $shouldBeSkipped) {
                    $line = $imports[self::className($classRef['class'])][1]; // <== get the line number of the import
                    self::report($classRef, $absFilePath, $line);
                    $fix && self::deleteFQCN($absFilePath, $classRef);
                }
            } elseif ($namespace && self::isInSameNamespace($namespace, $classRef['class']) && ! self::conflictingAlias($classRef['class'], $imports)) {
                $hasError = true;
                if (! $shouldBeSkipped) {
                    self::reportSameNamespace($classRef, $absFilePath);
                    $fix && self::deleteFQCN($absFilePath, $classRef);
                }
            } else {
                $imports2 = self::restructureImports($imports);
                if (isset($imports2[ltrim($classRef['class'])])) {
                    $hasError = true;
                    $alias = $imports2[ltrim($classRef['class'])][1];
                    ! $shouldBeSkipped && self::reportAliasImported($absFilePath, $alias, $classRef);
                }
            }
        }

        return $hasError;
    }

    private static function restructureImports(array $imports): array
    {
        return Loop::mapKey($imports, fn ($import, $key) => ['\\'.$import[0] => [$import[1], $key]]);
    }

    public static function deleteFQCN($absFilePath, $classRef)
    {
        $line = $classRef['line'];
        $classRef = $classRef['class'];
        $lines = file($absFilePath);
        $count = 0;

        $new = str_replace([$classRef], self::className($classRef), $lines[$line - 1], $count);
        if ($count === 1) {
            $lines[$line - 1] = $new;
            file_put_contents($absFilePath, implode('', $lines));

            return true;
        } elseif ($count > 1) {
            $className = self::className($classRef);
            $search = [$classRef.' ', $classRef.'(', $classRef.'::', $classRef.')', $classRef.';'];
            $replace = [$className.' ', $className.'(', $className.'::', $className.')', $className.';'];

            $new = str_replace($search, $replace, $lines[$line - 1], $count);
            if ($count === 1) {
                $lines[$line - 1] = $new;
                file_put_contents($absFilePath, implode('', $lines));

                return true;
            }
        }

        return false;
    }

    private static function reportAliasImported($absFilePath, $alias, $classRef)
    {
        $header = 'FQCN is already imported with an alias: '.$alias;
        $body = $classRef['class'].' can be replaced with: '.$alias;

        ErrorPrinter::singleton()->simplePendError(
            $body, $absFilePath, $classRef['line'], 'FQCN', $header
        );
    }

    private static function reportSameNamespace($classRef, string $absFilePath)
    {
        $header = 'FQCN is already on the same namespace.';

        ErrorPrinter::singleton()->simplePendError(
            $classRef['class'], $absFilePath, $classRef['line'], 'FQCN', $header
        );
    }

    private static function report(array $classRef, string $absFilePath, $line)
    {
        $header = 'FQCN is already imported at line: '.$line;

        ErrorPrinter::singleton()->simplePendError(
            $classRef['class'], $absFilePath, $classRef['line'], 'FQCN', $header
        );
    }

    private static function className($class)
    {
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        return basename($class);
    }
}
