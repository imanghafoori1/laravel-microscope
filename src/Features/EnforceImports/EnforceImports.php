<?php

namespace Imanghafoori\LaravelMicroscope\Features\EnforceImports;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Features\CheckExtraFQCN\ExtraFQCN;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\SearchReplace\Searcher;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class EnforceImports implements Check
{
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

    public static function check(PhpFileDescriptor $file)
    {
        if (CachedFiles::isCheckedBefore('EnforceImports', $file)) {
            return;
        }

        $tokens = $file->getTokens();
        $absFilePath = $file->getAbsolutePath();
        $imports = (self::$importsProvider)($file);

        $classRefs = ImportsAnalyzer::findClassRefs($tokens, $absFilePath, $imports);

        $hasError = self::checkClassRef($classRefs, $imports, $file);

        if ($hasError === false) {
            CachedFiles::put('EnforceImports', $file);
        }
    }

    public static function setOptions($noFix, $onlyRefs, $provider, $onError, $mutator)
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
        $original = file_get_contents($file->getAbsolutePath());

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

            $className = basename($classRef['class']);

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
        return isset($imports[basename($class)]);
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
        foreach ($imports as $key => $import) {
            $imports['\\'.$import[0]] = [$import[1], $key];
            unset($imports[$key]);
        }

        return $imports;
    }

    private static function refIsDeleted(array $deletes, string $className, string $class): bool
    {
        return isset($deletes[$className]) && $deletes[$className] !== $class;
    }

    private static function report(array $replacedRefs, PhpFileDescriptor $file): void
    {
        foreach ($replacedRefs as $classRef => $line) {
            (self::$onError)($classRef, $file, $line);
        }
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
                if ($only === basename($class)) {
                    return true;
                }
            }
        }

        return false;
    }
}
