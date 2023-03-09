<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Support\Str;
use ImanGhafoori\ComposerJson\NamespaceCalculator;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\TokenAnalyzer\FileManipulator;
use Symfony\Component\Console\Terminal;

class CheckStringy
{
    public static function check($tokens, $absFilePath)
    {
        (new self())->checkStringy($tokens, $absFilePath);
    }

    public function checkStringy($tokens, $absFilePath)
    {
        $errorPrinter = resolve(ErrorPrinter::class);
        foreach (ComposerJson::readAutoload() as $psr4) {
            $namespaces = array_keys($psr4);
            foreach ($tokens as $token) {
                if ($token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                    continue;
                }

                $classPath = \trim($token[1], '\'\"');

                if (! $this->isPossiblyClassyString($namespaces, $classPath)) {
                    continue;
                }

                if (! \class_exists(\str_replace('\\\\', '\\', $classPath))) {
                    if (self::refersToDir($classPath)) {
                        continue;
                    }
                    $errorPrinter->wrongUsedClassError($absFilePath, $token[1], $token[2]);
                    continue;
                }

                $errorPrinter->printLink($absFilePath, $token[2]);
                $command = app('current.command');

                if (! self::ask($command, $token, $absFilePath)) {
                    continue;
                }

                $classPath = $this->getClassyPath($classPath);
                $command->info('<fg=green>✔ Replaced with: </><fg=red>'.$classPath.'</>');

                $contextClass = ComposerJson::make()->getNamespacedClassFromPath($absFilePath);

                if (NamespaceCalculator::haveSameNamespace($contextClass, $classPath)) {
                    $classPath = trim(class_basename($classPath), '\\');
                }

                FileManipulator::replaceFirst($absFilePath, $token[1], $classPath);
                $width = (new Terminal)->getWidth() - 4;
                $command->info(' <fg='.config('microscope.colors.line_separator').'>'.str_repeat('_', $width).'</>');
            }
        }
    }

    protected function getClassyPath($string)
    {
        ($string[0] !== '\\') && ($string = '\\'.$string);
        $string .= '::class';

        return str_replace('\\\\', '\\', $string);
    }

    private function isPossiblyClassyString($namespaces, $classPath): bool
    {
        $chars = ['@', ' ', ',', ':', '/', '.', '-'];

        return Str::contains($classPath, $namespaces) &&
            ! in_array($classPath, $namespaces) &&
            ! Str::contains($classPath, $chars) &&
            ! Str::endsWith($classPath, '\\');
    }

    private static function ask($command, $token, $absFilePath)
    {
        $command->getOutput()->text($token[2].' |'.file($absFilePath)[$token[2] - 1]);
        $text = 'Replace: <fg=blue>'.$token[1].'</> with <fg=blue>::class</> version of it?';

        return $command->getOutput()->confirm($text, true);
    }

    private static function refersToDir(string $classPath): bool
    {
        return is_dir(base_path(ComposerJson::make()->getRelativePathFromNamespace($classPath)));
    }
}
