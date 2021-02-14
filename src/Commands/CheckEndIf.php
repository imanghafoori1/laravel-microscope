<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\Refactor;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Refactors\SyntaxNormalizer;

class CheckEndIf extends Command
{
    protected $signature = 'check:endif {--t|test : backup the changed files}';

    protected $description = 'replaces endif with curly brackets.';

    public function handle()
    {
        if (! $this->startWarning()) {
            return;
        }

        $psr4 = ComposerJson::readAutoload();

        $fixedFilesCount = 0;
        foreach ($psr4 as $psr4Namespace => $psr4Path) {
            $files = FilePath::getAllPhpFiles($psr4Path);
            foreach ($files as $file) {
                $path = $file->getRealPath();
                $tokens = token_get_all(file_get_contents($path));
                if (empty($tokens) || $tokens[0][0] !== T_OPEN_TAG) {
                    continue;
                }

                try {
                    $tokens = SyntaxNormalizer::normalizeSyntax($tokens, true);
                } catch (\Exception $e) {
                    dump('(O_o)   Well, It seems we had some problem parsing the contents of:   (o_O)');
                    dump('Submit an issue on github: https://github.com/imanghafoori1/microscope');
                    dump('Send us the contents of: '.$path);
                    continue;
                }

                if (! SyntaxNormalizer::$hasChange || ! $this->getConfirm($path)) {
                    continue;
                }

                Refactor::saveTokens($path, $tokens, $this->option('test'));

                $fixedFilesCount++;
            }
        }

        $this->printFinalMsg($fixedFilesCount);

        return app(ErrorPrinter::class)->hasErrors() ? 1 : 0;
    }

    private function fix($filePath, $tokens, $tries)
    {
        Refactor::saveTokens($filePath, $tokens, $this->option('test'));

        $this->warn(PHP_EOL.$tries.' fixes applied to: '.class_basename($filePath));
    }

    private function printFinalMsg($fixed)
    {
        if ($fixed > 0) {
            $msg = 'Hooray!!!, '.$fixed.' files were flattened by the microscope.';
        } else {
            $msg = 'Congratulations, your code base does not seems to need any fix.';
        }
        $this->info(PHP_EOL.$msg);
        $this->info('     \(^_^)/    You Rock    \(^_^)/    ');
    }

    private function getConfirm($filePath)
    {
        $filePath = FilePath::getRelativePath($filePath);

        return $this->output->confirm('Replacing endif in: '.$filePath, true);
    }

    private function startWarning()
    {
        $this->info('Checking for Early Returns...');
        $this->warn('This command is going to make changes to your files!');

        return $this->output->confirm('Do you have committed everything in git?', true);
    }
}
