<?php

namespace Imanghafoori\LaravelMicroscope\Features\EnforceImports;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Features\CheckExtraFQCN\ExtraFQCN;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\SearchReplace\Searcher;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class EnforceImports implements Check
{
    use CachedCheck;

    /**
     * @var bool
     */
    public static $fix = true;

    /**
     * @var array
     */
    public static $onlyRefs;

    /**
     * @var \Closure
     */
    public static $importsProvider;

    /**
     * @var \Closure
     */
    public static $onError;

    /**
     * @var \Closure
     */
    public static $mutator;

    /**
     * @var string
     */
    private static $cacheKey = 'EnforceImports';

    public static function performCheck(PhpFileDescriptor $file): bool
    {
        $tokens = $file->getTokens();
        $absFilePath = $file->getAbsolutePath();
        $imports = (self::$importsProvider)($file);

        $classRefs = ImportsAnalyzer::findClassRefs($tokens, $absFilePath, $imports);

        return self::checkClassRef($classRefs, $imports, $file);
    }

    public static function setOptions($noFix, $onlyRefs, $provider, $onError, $mutator = null)
    {
        if (is_string($onlyRefs)) {
            $onlyRefs = explode(',', $onlyRefs);
        }

        self::$fix = ! $noFix;
        self::$onlyRefs = $onlyRefs;
        self::$importsProvider = $provider;
        self::$onError = $onError;
        self::$mutator = $mutator;
    }

    private static function checkClassRef(array $classRefs, array $imports, PhpFileDescriptor $file): bool
    {
        $hasError = false;
        $namespace = $classRefs[1];
        $imports = array_values($imports)[0];
        $replacedRefs = [];
        $deletes = [];
        $original = $file->getContent();

        foreach ($classRefs[0] as $classRef) {
            if (! self::shouldBeImported($classRef['class'], $imports, $namespace)) {
                continue;
            }

            $hasError = true;

            $onlyRefs = self::$onlyRefs;
            $shouldBeSkipped = $onlyRefs && ! self::contains($onlyRefs, $classRef['class']);

            if ($shouldBeSkipped) {
                continue;
            }

            $className = self::className($classRef['class']);

            if ($namespace && ! self::refIsDeleted($deletes, $className, $classRef['class'])) {
                $absFilePath = $file->getAbsolutePath();
                if ($file->getFileName() !== $className.'.php') {
                    self::$fix && ExtraFQCN::deleteFQCN($absFilePath, $classRef);
                    $deletes[$className] = $classRef['class'];
                    $replacedRefs[$classRef['class']] = $classRef['line'];
                }
            }
        }

        $reverted = false;
        if (self::$fix) {
            foreach ($replacedRefs as $classRef => $_) {
                $replacements = self::insertImport($file, $classRef);
                // in case we are not able to insert imports at the top:
                if (count($replacements) === 0) {
                    file_put_contents($file->getAbsolutePath(), $original);
                    $hasError = $reverted = true;
                    break;
                }
            }
        }

        ! $reverted && self::report($replacedRefs, $file);

        return $hasError;
    }

    private static function insertImport(PhpFileDescriptor $file, $classRef)
    {
        $classRef = ltrim($classRef, '\\');

        if (self::$mutator) {
            $classRef = (self::$mutator)($classRef);
        }

        [$string, $replacements] = Searcher::searchReplaceFirst([
            [
                'ignore_whitespaces' => false,
                'name' => 'enforceImports',
                'search' => 'namespace <any>;<white_space>?',
                'replace' => 'namespace <1>;'.PHP_EOL.PHP_EOL.'use '.$classRef.';'.PHP_EOL,
            ],
        ], $file->getTokens(true));
        file_put_contents($file->getAbsolutePath(), $string);

        return $replacements;
    }

    private static function isDirectlyImported($class, $imports): bool
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

    private static function restructureImports(array $imports): array
    {
        return Loop::mapKey(
            $imports,
            fn ($import, $key) => [
                '\\'.$import[0] => [$import[1], $key],
            ]
        );
    }

    private static function refIsDeleted(array $deletes, string $className, string $class): bool
    {
        return isset($deletes[$className]) && $deletes[$className] !== $class;
    }

    private static function report(array $replacedRefs, PhpFileDescriptor $file): void
    {
        Loop::over(
            $replacedRefs,
            fn ($line, $classRef) => (self::$onError)($classRef, $file, $line)
        );
    }

    private static function shouldBeImported($class, $imports, $namespace)
    {
        if ($class[0] !== '\\') {
            return false;
        }

        if (self::isDirectlyImported($class, $imports)) {
            return false;
        } elseif ($namespace && self::isInSameNamespace($namespace, $class)) {
            return false;
        } else {
            $imports2 = self::restructureImports($imports);
            if (isset($imports2[ltrim($class)])) {
                return false;
            }
        }

        return true;
    }

    private static function contains($onlyRefs, $class): bool
    {
        foreach ($onlyRefs as $only) {
            if ($only[0] === '\\') {
                if ($only === $class) {
                    return true;
                }
            } else {
                if ($only === self::className($class)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function className($class)
    {
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        return basename($class);
    }
}
