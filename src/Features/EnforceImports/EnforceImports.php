<?php

namespace Imanghafoori\LaravelMicroscope\Features\EnforceImports;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckExtraFQCN\ExtraFQCN;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\SearchReplace\Searcher;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class EnforceImports implements Check
{
    public static function check(PhpFileDescriptor $file, $imports = [])
    {
        if (CachedFiles::isCheckedBefore('EnforceImports', $file)) {
            return;
        }

        $tokens = $file->getTokens();
        $absFilePath = $file->getAbsolutePath();
        $class = $imports[1];
        $imports = ($imports[0])($file);

        $classRefs = ImportsAnalyzer::findClassRefs($tokens, $absFilePath, $imports);

        $hasError = self::checkClassRef($classRefs, $imports, $file, $class);

        if ($hasError === false) {
            CachedFiles::put('EnforceImports', $file);
        }
    }

    private static function checkClassRef(array $classRefs, array $imports, PhpFileDescriptor $file, $class): bool
    {
        $hasError = false;
        $namespace = $classRefs[1];
        $imports = array_values($imports)[0];
        $replacedRefs = [];
        $deletes = [];
        $original = file_get_contents($file->getAbsolutePath());

        foreach ($classRefs[0] as $classRef) {
            if ($classRef['class'][0] !== '\\') {
                continue;
            }

            if (self::isDirectlyImported($classRef['class'], $imports)) {
                continue;
            } elseif ($namespace && self::isInSameNamespace($namespace, $classRef['class'])) {
                continue;
            } else {
                $imports2 = self::restructureImports($imports);
                if (isset($imports2[ltrim($classRef['class'])])) {
                    continue;
                }
            }

            $shouldBeSkipped = $class && self::contains(basename($classRef['class']), $class) === false;

            if ($shouldBeSkipped) {
                $hasError = true;
                continue;
            }

            $className = basename($classRef['class']);

            if ($namespace && ! (isset($deletes[$className]) && $deletes[$className] !== $classRef['class'])) {
                $absFilePath = $file->getAbsolutePath();
                if ($file->getFileName() !== $className.'.php') {
                    ExtraFQCN::deleteFQCN($absFilePath, $classRef);
                    $deletes[$className] = $classRef['class'];
                    $replacedRefs[$classRef['class']] = $classRef['line'];
                }
            }
        }

        $reverted = false;
        foreach ($replacedRefs as $classRef => $_) {
            $replacements = self::insertImport($file, $classRef);
            // in case we are not able to insert imports at the top:
            if (count($replacements) === 0) {
                file_put_contents($file->getAbsolutePath(), $original);
                $hasError = $reverted = true;
                break;
            }
        }

        $header = 'FQCN got imported at the top';
        if (! $reverted) {
            foreach ($replacedRefs as $classRef => $line) {
                ErrorPrinter::singleton()->simplePendError($classRef, $file->getAbsolutePath(), $line, 'force_import', $header);
            }
        }

        return $hasError;
    }

    private static function insertImport(PhpFileDescriptor $file, $classRef)
    {
        [$string, $replacements] = Searcher::searchReplaceFirst([
            [
                'ignore_whitespaces' => false,
                'name' => 'enforceImports',
                'search' => 'namespace <any>;<white_space>?',
                'replace' => 'namespace <1>;'.PHP_EOL.PHP_EOL.'use '.ltrim($classRef, '\\').';'.PHP_EOL,
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

    private static function contains($haystack, $needle)
    {
        foreach (explode(',', $needle) as $item) {
            if (strpos($haystack, $item) !== false) {
                return true;
            }
        }

        return false;
    }
}
