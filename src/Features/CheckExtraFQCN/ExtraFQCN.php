<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraFQCN;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class ExtraFQCN implements Check
{
    public static function check(PhpFileDescriptor $file, $imports = [])
    {
        if (CachedFiles::isCheckedBefore('ExtraFQCN', $file)) {
            return;
        }

        $tokens = $file->getTokens();
        $absFilePath = $file->getAbsolutePath();
        $imports = ($imports[0])($file);
        $classRefs = ImportsAnalyzer::findClassRefs($tokens, $absFilePath, $imports);
        $imports = self::restructureImports($imports);

        $hasError = self::checkClassRef($classRefs, $imports, $absFilePath);

        if ($hasError === false) {
            CachedFiles::put('ExtraFQCN', $file);
        }
    }

    private static function isImported($class, $imports): bool
    {
        return isset($imports[$class]) && $imports[$class][1] === basename($class);
    }

    private static function report(array $classRef, string $absFilePath, $line)
    {
        $header = 'FQCN is already imported at line: '.$line;

        ErrorPrinter::singleton()->simplePendError($classRef['class'], $absFilePath, $classRef['line'], 'FQCN', $header);
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

    private static function reportSameNamespace($classRef, string $absFilePath)
    {
        $header = 'FQCN is already on the same namespace.';

        ErrorPrinter::singleton()->simplePendError(
            $classRef['class'], $absFilePath, $classRef['line'], 'FQCN', $header
        );
    }

    private static function checkClassRef(array $classRefs, array $imports, string $absFilePath): bool
    {
        $hasError = false;
        $namespace = $classRefs[1];
        foreach ($classRefs[0] as $classRef) {
            if ($classRef['class'][0] !== '\\') {
                continue;
            }

            if (self::isImported($classRef['class'], $imports)) {
                $hasError = true;
                $line = $imports[$classRef['class']][0];
                self::report($classRef, $absFilePath, $line);
            } elseif ($namespace && self::isInSameNamespace($namespace, $classRef['class'])) {
                $hasError = true;
                self::reportSameNamespace($classRef, $absFilePath);
            }
        }

        return $hasError;
    }

    private static function restructureImports(array $imports): array
    {
        $imports = array_values($imports)[0];

        foreach ($imports as $key => $import) {
            $imports['\\'.$import[0]] = [$import[1], $key];
            unset($imports[$key]);
        }

        return $imports;
    }
}
