<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckClassyStrings\Checks;

use Generator;
use ImanGhafoori\ComposerJson\NamespaceCalculator;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Foundations\Console;
use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\FileManipulator;

use function class_basename;

class ClassyStringProcessor
{
    public static function process(Generator $tokens, PhpFileDescriptor $file): bool
    {
        foreach ($tokens as [$token, $classPath]) {
            $lineNum = $token[2];
            if (! class_exists($classPath)) {
                if (self::refersToDir($classPath)) {
                    continue;
                }
                self::error($token[1], $file, $lineNum);

                continue;
            }

            if (! self::ask($lineNum, $token[1], $file)) {
                continue;
            }
            $replacement = self::getClassPath($classPath, $file);
            self::performReplacementProcess($token[1], $replacement, $file);
        }

        return isset($token);
    }

    private static function error($class, PhpFileDescriptor $file, $lineNum): void
    {
        ErrorPrinter::singleton()->simplePendError(
            $class,
            $file,
            $lineNum,
            'wrongStringyClassError',
            CheckStringyMsg::classDoesNotExist($class)
        );
    }

    private static function getClassPath(string $classPath, PhpFileDescriptor $file)
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

    /**
     * @return bool
     */
    private static function ask($lineNumber, $classPath, PhpFileDescriptor $file)
    {
        Console::getInstance()->text(PHP_EOL.CheckStringyMsg::getLineContents($lineNumber, $file));
        Console::getInstance()->text(ErrorPrinter::getLink($file->getAbsolutePath(), $lineNumber));

        return Console::confirm(CheckStringyMsg::question($classPath));
    }

    private static function refersToDir($classPath)
    {
        return is_dir(BasePath::$path.DIRECTORY_SEPARATOR.ComposerJson::make()->getRelativePathFromNamespace($classPath));
    }

    private static function performReplacementProcess($classyString, $classPath, PhpFileDescriptor $file)
    {
        $console = Console::getInstance();
        $console->writeln(CheckStringyMsg::successfulReplacementMsg($classPath));

        // todo: should replace tokens not the file contents.
        FileManipulator::replaceFirst($file->getAbsolutePath(), $classyString, $classPath);

        $console->writeln(ErrorPrinter::lineSeparator());
    }
}
