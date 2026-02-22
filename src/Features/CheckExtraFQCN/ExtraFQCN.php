<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraFQCN;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Foundations\UseStatementParser;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class ExtraFQCN implements Check
{
    use CachedCheck;

    /**
     * @var string
     */
    private static $cacheKey = 'extra_fqcn';

    public static $fix;

    public static $imports = UseStatementParser::class;

    public static $class;

    public static function performCheck(PhpFileDescriptor $file): bool
    {
        $imports = self::$imports::parse($file);
        $classRefs = ImportsAnalyzer::findClassRefs(
            $file->getTokens(),
            $file->getAbsolutePath(),
            $imports
        );
        $hasError = self::checkClassRef($classRefs, $imports, $file);

        return $hasError;
    }

    private static function isDirectlyImported($class, $imports): bool
    {
        return isset($imports[self::className($class)]) && $imports[self::className($class)][0] === ltrim($class, '\\');
    }

    private static function isConflictingAlias($class, $imports)
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

    private static function checkClassRef($classRefs, $imports, PhpFileDescriptor $file): bool
    {
        $fix = self::$fix;
        $class = self::$class;
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
                    ExtraFqcnHandler::reportAlreadyImported($classRef, $file, $line);
                    $fix && self::deleteFQCN($file, $classRef);
                }
            } elseif ($namespace && self::isInSameNamespace($namespace, $classRef['class']) && ! self::isConflictingAlias($classRef['class'], $imports)) {
                $hasError = true;
                if (! $shouldBeSkipped) {
                    ExtraFqcnHandler::reportSameNamespace($classRef, $file, $fix);
                    $fix && self::deleteFQCN($file, $classRef);
                }
            } else {
                $imports2 = self::restructureImports($imports);
                $aliasToken = $imports2[ltrim($classRef['class'])] ?? '';
                if ($aliasToken) {
                    $hasError = true;
                    $alias = $aliasToken[1];
                    ! $shouldBeSkipped && ExtraFqcnHandler::reportAliasImported($file, $alias, $classRef);
                }
            }
        }

        return $hasError;
    }

    private static function restructureImports(array $imports): array
    {
        return Loop::mapKey($imports, fn ($import, $key) => ['\\'.$import[0] => [$import[1], $key]]);
    }

    public static function deleteFQCN(PhpFileDescriptor $file, $classRef)
    {
        $line = $classRef['line'];
        $classRef = $classRef['class'];
        $lines = file($file->getAbsolutePath());
        $count = 0;

        $new = str_replace([$classRef], self::className($classRef), $lines[$line - 1], $count);
        if ($count === 1) {
            $lines[$line - 1] = $new;
            $file->putContents(implode('', $lines));

            return true;
        }

        if ($count > 1) {
            [$count, $new] = self::replace($classRef, $lines[$line - 1]);
            if ($count > 0) {
                $lines[$line - 1] = $new;
                $file->putContents(implode('', $lines));

                return true;
            }
        }

        return false;
    }

    private static function className($class)
    {
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        return basename($class);
    }

    public static function configure($class, $fix): void
    {
        self::$class = $class;
        self::$fix = $fix;
    }

    public static function reset()
    {
        self::configure(null, false);
    }

    private static function replace($classRef, $subject): array
    {
        $className = self::className($classRef);
        $search = [$classRef.' ', $classRef.'(', $classRef.'::', $classRef.')', $classRef.';'];
        $replace = [$className.' ', $className.'(', $className.'::', $className.')', $className.';'];
        $count = 0;
        $new = str_replace($search, $replace, $subject, $count);

        return [$count, $new];
    }
}
