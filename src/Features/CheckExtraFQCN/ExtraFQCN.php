<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraFQCN;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;
use Imanghafoori\TokenAnalyzer\ImportsAnalyzer;

class ExtraFQCN implements Check
{
    public static $cache = [];

    public static function check(PhpFileDescriptor $file, $imports = [])
    {
        if (CachedFiles::isCheckedBefore('ExtraFQCN', $file)) {
            return;
        }

        $tokens = $file->getTokens();
        $absFilePath = $file->getAbsolutePath();
        $imports = ($imports[0])($file);
        $classRefs = ImportsAnalyzer::findClassRefs($tokens, $absFilePath, $imports);
        $imports = array_values($imports)[0];
        $hasError = false;

        foreach ($imports as $key => $import) {
            $imports['\\'.$import[0]] = [$import[1], $key];
            unset($imports[$key]);
        }

        foreach ($classRefs[0] as $classRef) {
            if (self::isImported($classRef['class'], $imports)) {
                $hasError = true;
                $line = $imports[$classRef['class']][0];
                self::report($classRef, $absFilePath, $line);
            }
        }

        if ($hasError === false) {
            CachedFiles::put('ExtraFQCN', $file);
        }
    }

    private static function isImported($class, $imports): bool
    {
        return $class[0] === '\\' && isset($imports[$class]) && $imports[$class][1] === basename($class);
    }

    private static function report(array $classRef, string $absFilePath, $line)
    {
        $header = 'FQCN is already imported at line: '.$line;

        ErrorPrinter::singleton()->simplePendError($classRef['class'], $absFilePath, $classRef['line'], 'FQCN', $header);
    }
}
