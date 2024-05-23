<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckClassyStrings;

use ImanGhafoori\ComposerJson\NamespaceCalculator;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\FileManipulator;
use Imanghafoori\TokenAnalyzer\Str;

class CheckStringy implements Check
{
    public static function check(PhpFileDescriptor $file)
    {
        $errorPrinter = resolve(ErrorPrinter::class);
        foreach (ComposerJson::readPsr4() as $psr4) {
            $namespaces = array_keys($psr4);
            foreach ($file->getTokens() as $token) {
                if ($token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                    continue;
                }

                $classPath = trim($token[1], '\'\"');

                if (! self::isPossiblyClassyString($namespaces, $classPath)) {
                    continue;
                }

                $lineNum = $token[2];
                $absFilePath = $file->getAbsolutePath();
                if (! class_exists(str_replace('\\\\', '\\', $classPath))) {
                    if (self::refersToDir($classPath)) {
                        continue;
                    }
                    $errorPrinter->wrongUsedClassError($absFilePath, $token[1], $lineNum);
                    continue;
                }

                $errorPrinter->printLink($absFilePath, $lineNum);
                $command = app('current.command');

                if (! self::ask($errorPrinter->printer, $lineNum, $token[1], $file)) {
                    continue;
                }
                $replacement = self::getClassPath($classPath, $file);
                self::performReplacementProcess($token[1], $replacement, $command, $file);
            }
        }
    }

    private static function isPossiblyClassyString($namespaces, $classPath)
    {
        $chars = ['@', ' ', ',', ':', '/', '.', '-'];

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
        return is_dir(base_path(ComposerJson::make()->getRelativePathFromNamespace($classPath)));
    }

    private static function performReplacementProcess($classyString, $classPath, $command, PhpFileDescriptor $file)
    {
        $command->info(CheckStringyMsg::successfulReplacementMsg($classPath));

        // todo: should replace tokens not the file contents.
        FileManipulator::replaceFirst($file->getAbsolutePath(), $classyString, $classPath);

        $command->info(CheckStringyMsg::lineSeparator());
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
