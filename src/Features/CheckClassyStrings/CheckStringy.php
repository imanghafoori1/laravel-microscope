<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckClassyStrings;

use ImanGhafoori\ComposerJson\NamespaceCalculator;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\CachedCheck;
use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\FileManipulator;
use Imanghafoori\TokenAnalyzer\Str;

class CheckStringy implements Check
{
    use CachedCheck;

    public static $cacheKey = 'stringy_classes';

    public static $command = null;

    public static function performCheck(PhpFileDescriptor $file)
    {
        $errorPrinter = ErrorPrinter::singleton();

        $hasError = false;
        foreach (ComposerJson::readPsr4() as $psr4) {
            $namespaces = array_keys($psr4);
            foreach ($file->getTokens() as $token) {
                if ($token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                    continue;
                }

                $classPath = trim($token[1], $token[1][0] === "'" ? "'" : '"');
                $classPath = str_replace('\\\\', '\\', $classPath);

                if (! self::isPossiblyClassyString($namespaces, $classPath)) {
                    continue;
                }

                $hasError = true;
                $lineNum = $token[2];
                if (! class_exists($classPath)) {
                    if (self::refersToDir($classPath)) {
                        continue;
                    }
                    ErrorPrinter::singleton()->simplePendError(
                        $token[1],
                        $file,
                        $lineNum,
                        'wrongUsedClassError',
                        'Class does not exist:'
                    );

                    continue;
                }

                $errorPrinter->printLink($file, $lineNum);

                if (! self::ask($errorPrinter->printer, $lineNum, $token[1], $file)) {
                    continue;
                }
                $replacement = self::getClassPath($classPath, $file);
                self::performReplacementProcess($token[1], $replacement, $file);
            }
        }

        return $hasError;
    }

    private static function isPossiblyClassyString($namespaces, $classPath)
    {
        $chars = ['@', ' ', ',', ':', '/', '.', '-', '\'', '"', '\\\\'];

        return Str::contains($classPath, $namespaces) &&
            ! in_array($classPath, $namespaces) &&
            ! Str::contains($classPath, $chars) &&
            ! Str::endsWith($classPath, '\\');
    }

    private static function ask($printer, $lineNumber, $classPath, PhpFileDescriptor $file)
    {
        $printer->text(CheckStringyMsg::getLineContents($lineNumber, $file));

        return $printer->confirm(CheckStringyMsg::question($classPath), true);
    }

    private static function refersToDir($classPath)
    {
        return is_dir(BasePath::$path.DIRECTORY_SEPARATOR.ComposerJson::make()->getRelativePathFromNamespace($classPath));
    }

    private static function performReplacementProcess($classyString, $classPath, PhpFileDescriptor $file)
    {
        $command = self::$command;
        $command->info(CheckStringyMsg::successfulReplacementMsg($classPath));

        // todo: should replace tokens not the file contents.
        FileManipulator::replaceFirst($file->getAbsolutePath(), $classyString, $classPath);

        $command->info(ErrorPrinter::lineSeparator());
    }

    public static function getClassPath(string $classPath, PhpFileDescriptor $file)
    {
        // Put back-slash at the beginning.
        ($classPath[0] !== '\\') && ($classPath = '\\'.$classPath);

        $classPath .= '::class';

        // Remove possible double back-slash:
        $classPath = str_replace('\\\\', '\\', $classPath);

        // Remove unnecessary qualifier if possible.
        $contextClass = $file->getNamespace();

        if (NamespaceCalculator::haveSameNamespace($contextClass, $classPath)) {
            $classPath = trim(class_basename($classPath), '\\');
        }

        return $classPath;
    }
}
