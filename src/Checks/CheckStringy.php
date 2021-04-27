<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;
use Imanghafoori\LaravelMicroscope\Analyzers\ReplaceLine;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckStringy
{
    public static function check($tokens, $absFilePath)
    {
        (new self())->checkStringy($tokens, $absFilePath);
    }

    public function checkStringy($tokens, $absFilePath)
    {
        $psr4 = ComposerJson::readAutoload();
        $namespaces = array_keys($psr4);
        $errorPrinter = resolve(ErrorPrinter::class);
        foreach ($tokens as $token) {
            if (! $this->isPossiblyClassyString($token, $namespaces)) {
                continue;
            }

            $classPath = \trim($token[1], '\'\"');
            if (! \class_exists($classPath)) {
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
            $command->info('Replacing: '.$token[1].'  with: '.$classPath);

            $contextClass = ReplaceLine::getNamespaceFromRelativePath($absFilePath);

            if (NamespaceCorrector::haveSameNamespace($contextClass, $classPath)) {
                $classPath = trim(class_basename($classPath), '\\');
            }

            ReplaceLine::replaceFirst($absFilePath, $token[1], $classPath);
            $command->info('====================================');
        }
    }

    protected function getClassyPath($string)
    {
        ($string[0] !== '\\') && ($string = '\\'.$string);
        $string .= '::class';

        return str_replace('\\\\', '\\', $string);
    }

    private function isPossiblyClassyString($token, $namespaces)
    {
        $chars = ['@', ' ', ',', ':', '/', '.', '-'];

        return $token[0] == T_CONSTANT_ENCAPSED_STRING &&
            Str::contains($token[1], $namespaces) &&
            ! in_array($token[1], $namespaces) &&
            ! Str::contains($token[1], $chars) &&
            ! Str::endsWith($token[1], '\\');
    }

    private static function ask($command, $token, $absFilePath)
    {
        $command->getOutput()->text($token[2].' |'.file($absFilePath)[$token[2] - 1]);
        $text = 'Do you want to replace: '.$token[1].' with ::class version of it?';

        return $command->getOutput()->confirm($text, true);
    }

    private static function refersToDir(string $classPath)
    {
        return is_dir(base_path(NamespaceCorrector::getRelativePathFromNamespace($classPath)));
    }
}
