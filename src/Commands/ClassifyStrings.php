<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;
use Imanghafoori\LaravelMicroscope\Analyzers\ReplaceLine;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\CheckClasses;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Traits\LogsErrors;

class ClassifyStrings extends Command
{
    use LogsErrors;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:stringy_classes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replaces string references with ::class version of them.';

    public function handle(ErrorPrinter $errorPrinter)
    {
        $this->info('Checking strings...');

        $errorPrinter->printer = $this->output;

        $psr4 = ComposerJson::readKey('autoload.psr-4');
        $namespaces = array_keys($psr4);
        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);

            foreach ($files as $file) {
                $absFilePath = $file->getRealPath();

                $tokens = token_get_all(file_get_contents($absFilePath));
                foreach ($tokens as $token) {
                    if (! $this->isPossiblyClassyString($token, $namespaces)) {
                        continue;
                    }
                    $classPath = trim($token[1], '\'\"');
                    if (CheckClasses::isAbsent($classPath)) {
                        $relPath = NamespaceCorrector::getRelativePathFromNamespace($classPath);
                        // Is a correct namespace path, pointing to a directory
                        if (is_dir(base_path($relPath))) {
                            continue;
                        }
                        $errorPrinter->wrongUsedClassError($absFilePath, $token[1], $token[2]);
                        continue;
                    }

                    $errorPrinter->printLink($absFilePath, $token[2]);
                    $this->output->text($token[2].' |'.file($absFilePath)[$token[2] - 1]);
                    if ($this->output->confirm('Do you want to replace: '.$token[1].' with ::class version of it? ', true)) {
                        dump('Replacing: '.$token[1].'  with: '.$this->getClassyPath($classPath));
                        ReplaceLine::replaceFirst($absFilePath, $token[1], $this->getClassyPath($classPath));
                        $this->info('====================================');
                    }
                }
            }
        }

        $this->finishCommand($errorPrinter);
    }

    protected function getClassyPath($string)
    {
        ($string[0] !== '\\') && ($string = '\\'.$string);
        $string .= '::class';

        return $string;
    }

    private function isPossiblyClassyString($token, $namespaces)
    {
        $chars = ['@', ' ', ',', ':', '/', '.', '-'];

        return $token[0] == T_CONSTANT_ENCAPSED_STRING && Str::contains($token[1], $namespaces) && ! Str::contains($token[1], $chars);
    }
}
