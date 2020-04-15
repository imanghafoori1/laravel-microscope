<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Util;
use Imanghafoori\LaravelMicroscope\ReplaceLine;
use Imanghafoori\LaravelMicroscope\CheckClasses;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class ClassifyStrings extends Command
{
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

        $psr4 = Util::parseComposerJson('autoload.psr-4');
        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = CheckClasses::getAllPhpFiles($psr4Path);

            foreach ($files as $file) {
                $absFilePath = $file->getRealPath();

                $tokens = token_get_all(file_get_contents($absFilePath));
                foreach ($tokens as $token) {
                    if ($token[0] == T_CONSTANT_ENCAPSED_STRING &&
                        Str::contains($token[1], ['\\']) &&
                        class_exists(trim($token[1], '\'\"'))) {
                        $errorPrinter->printLink($absFilePath, $token[2]);
                        $this->output->text($token[2].' |'.file($absFilePath)[$token[2] - 1]);
                        $answer = $this->output->confirm('Do you want to replace: '.$token[1]. ' with ::class version of it? ' , true);
                        if ($answer) {
                            dump('Replacing: '.$token[1]. '  with: '. $this->getClassyPath($token));
                            ReplaceLine::replaceFirst($absFilePath, $token[1], $this->getClassyPath($token));
                            dump('====================================');
                        }
                    }
                }
            }
        }
    }

    protected function getClassyPath($token)
    {
        $string = trim($token[1], '\'\"');
        ($string[0] !== '\\') && ($string = '\\'.$string);
        $string .= '::class';

        return $string;
    }
}
