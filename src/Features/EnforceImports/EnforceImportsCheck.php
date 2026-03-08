<?php

namespace Imanghafoori\LaravelMicroscope\Features\EnforceImports;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Features\CheckExtraFQCN\FqcnDeleter;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Foundations\UseStatementParser;
use Imanghafoori\SearchReplace\Searcher;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class EnforceImportsCheck implements Check
{
    use CachedCheck;

    /**
     * @var bool
     */
    public static $fix = true;

    /**
     * @var string[]
     */
    public static $onlyRefs;

    /**
     * @var \Closure
     */
    public static $importsProvider = UseStatementParser::class;

    public static $onError = EnforceImportsHandler::class;

    /**
     * @var \Closure
     */
    public static $mutator;

    /**
     * @var string
     */
    private static $cacheKey = 'EnforceImports';

    /**
     * @var bool
     */
    public static $hasError;

    public static function performCheck(PhpFileDescriptor $file): bool
    {
        $tokens = $file->getTokens();
        $absFilePath = $file->getAbsolutePath();
        $imports = self::$importsProvider::parse($file);

        $classRefs = ImportsAnalyzer::findClassRefs($tokens, $absFilePath, $imports);

        return self::checkClassRef($classRefs, $imports, $file);
    }

    public static function setOptions($noFix, $onlyRefs, $mutator = null)
    {
        if (is_string($onlyRefs)) {
            $onlyRefs = explode(',', $onlyRefs);
        }

        self::$fix = ! $noFix;
        self::$onError && self::$onError::$noFix = $noFix;
        self::$onlyRefs = $onlyRefs;
        self::$mutator = $mutator;
    }

    private static function checkClassRef(array $classRefs, array $imports, PhpFileDescriptor $file): bool
    {
        self::$hasError = false;
        $namespace = $classRefs[1];
        $imports = array_values($imports)[0];
        $classRefs = FilterImports::refs($classRefs[0], $imports, $namespace);

        $classRefs = self::collectClassRefs($classRefs, self::$onlyRefs);

        $replacedRefs = self::deleteRefs($file, $classRefs, $namespace);

        if (self::$fix) {
            $reverted = self::insertImportForReplacedRefs($file, $replacedRefs);
        }

        ! isset($reverted) && self::$onError && self::report($replacedRefs, $file);

        return self::$hasError;
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
        $file->putContents($string);

        return $replacements;
    }

    private static function refIsDeleted(array $deletes, string $className, string $class): bool
    {
        return isset($deletes[$className]) && $deletes[$className] !== $class;
    }

    private static function report(array $replacedRefs, PhpFileDescriptor $file): void
    {
        Loop::over(
            $replacedRefs,
            fn ($line, $classRef) => self::$onError::handle($classRef, $file, $line)
        );
    }

    private static function contains($onlyRefs, $class): bool
    {
        foreach ($onlyRefs as $only) {
            if ($only[0] === '\\') {
                if ($only === $class) {
                    return true;
                }
            } elseif ($only === self::className($class)) {
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

    private static function collectClassRefs($classRefs, $onlyRefs)
    {
        foreach ($classRefs as $classRef) {
            if ($onlyRefs && ! self::contains($onlyRefs, $classRef['class'])) {
                continue;
            }

            yield $classRef;
        }
    }

    private static function deleteRefs(PhpFileDescriptor $file, $refs, $namespace): array
    {
        $deletes = [];
        $replacedRefs = [];
        foreach ($refs as $classRef) {
            $className = self::className($classRef['class']);

            if (! $namespace || self::refIsDeleted($deletes, $className, $classRef['class'])) {
                continue;
            }
            if ($file->getFileName() === $className.'.php') {
                continue;
            }
            self::$fix && FqcnDeleter::delete($file, $classRef);
            $deletes[$className] = $classRef['class'];
            $replacedRefs[$classRef['class']] = $classRef['line'];
        }

        return $replacedRefs;
    }

    private static function insertImportForReplacedRefs(PhpFileDescriptor $file, array $replacedRefs)
    {
        $reverted = null;
        $original = $file->getContent();
        foreach ($replacedRefs as $classRef => $_) {
            $replacements = self::insertImport($file, $classRef);
            // in case we are not able to insert imports at the top:
            if (count($replacements) === 0) {
                $file->putContents($original);
                self::$hasError = $reverted = true;

                break;
            }
        }

        return $reverted;
    }
}
