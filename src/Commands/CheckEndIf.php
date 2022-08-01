<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Exception;
use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\TokenAnalyzer\Refactor;
use Imanghafoori\TokenAnalyzer\SyntaxNormalizer;

class CheckEndIf extends Command
{
    protected $signature = 'check:endif {--t|test : backup the changed files}';

    protected $description = 'replaces endif with curly brackets.';

    public function handle()
    {
        if (! $this->startWarning()) {
            return null;
        }

        $fixedFilesCount = 0;
        foreach (ComposerJson::readAutoload() as $psr4) {
            foreach ($psr4 as $psr4Path) {
                $files = FilePath::getAllPhpFiles($psr4Path);
                foreach ($files as $file) {
                    $path = $file->getRealPath();
                    $tokens = token_get_all(file_get_contents($path));
                    if (empty($tokens) || $tokens[0][0] !== T_OPEN_TAG) {
                        continue;
                    }

                    try {
                        $tokens = SyntaxNormalizer::normalizeSyntax($tokens, true);
                    } catch (Exception $e) {
                        self::requestIssue($path);
                        continue;
                    }

                    if (! SyntaxNormalizer::$hasChange || ! $this->getConfirm($path)) {
                        continue;
                    }

                    Refactor::saveTokens($path, $tokens, $this->option('test'));

                    $fixedFilesCount++;
                }
            }
        }

        $this->printFinalMsg($fixedFilesCount);

        return app(ErrorPrinter::class)->hasErrors() ? 1 : 0;
    }

    private function printFinalMsg($fixed)
    {
        if ($fixed > 0) {
            $msg = 'Hooray!, '.$fixed.' files were transformed by the microscope.';
        } else {
            $msg = 'Congratulations, your code base does not seem to need any fix.';
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
        $this->info('Checking for endif\'s...');
        $this->warn('This command is going to make changes to your files!');

        return $this->output->confirm('Do you have committed everything in git?', true);
    }

    private static function requestIssue(string $path)
    {
        dump('(O_o)   Well, It seems we had some problem parsing the contents of:   (o_O)');
        dump('Submit an issue on github: https://github.com/imanghafoori1/microscope');
        dump('Send us the contents of: '.$path);
    }
}
