<?php

namespace Imanghafoori\LaravelMicroscope\Commands;

use Exception;
use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorTypes\CompactCall;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\SpyClasses\RoutePaths;
use Imanghafoori\TokenAnalyzer\FunctionCall;
use Imanghafoori\TokenAnalyzer\Ifs;
use Imanghafoori\TokenAnalyzer\TokenManager;

class CheckCompact extends Command
{
    protected $signature = 'check:compact';

    protected $description = 'Checks that compact() function calls are correct';

    public function handle()
    {
        event('microscope.start.command');
        $this->info('Checking compact() calls, fast and furious!  mm(~_~)mm  ');

        $this->checkRoutePaths(RoutePaths::get());
        $this->checkPsr4Classes();

        event('microscope.finished.checks', [$this]);

        return app(ErrorPrinter::class)->hasErrors() ? 1 : 0;
    }

    private function checkPathForCompact($absPath)
    {
        $tokens = token_get_all(file_get_contents($absPath));

        foreach ($tokens as $i => $token) {
            if ($token[0] !== T_FUNCTION) {
                continue;
            }

            $methodBody = $this->readMethodBodyAsTokens($tokens, $i);

            if ($methodBody === false) {
                continue;
            }

            $signatureVars = $this->collectSignatureVars($tokens, $i);
            $this->checkMethodBodyForCompact($absPath, $methodBody, $signatureVars);
        }
    }

    private function checkRoutePaths($paths)
    {
        foreach ($paths as $filePath) {
            $this->checkPathForCompact($filePath);
        }
    }

    private function checkPsr4Classes()
    {
        foreach (ComposerJson::readAutoload() as $psr4) {
            foreach ($psr4 as $_namespace => $dirPath) {
                foreach (FilePath::getAllPhpFiles($dirPath) as $filePath) {
                    $this->checkPathForCompact($filePath->getRealPath());
                }
            }
        }
    }

    private function checkMethodBodyForCompact($absPath, $methodBody, $vars)
    {
        foreach ($methodBody as $c => $token) {
            ($token[0] == T_VARIABLE) && $vars[$token[1]] = null;

            if (! ($pp = FunctionCall::isGlobalCall('compact', $methodBody, $c))) {
                continue;
            }

            [, $compactedVars,] = Ifs::readCondition($methodBody, $pp);
            $compactVars = [];
            foreach ($compactedVars as $uu => $var) {
                $var[0] == T_CONSTANT_ENCAPSED_STRING && $compactVars[] = '$'.\trim($var[1], '\'\"');
            }
            $compactVars = array_flip($compactVars);

            unset($vars['$this']);
            $missingVars = array_diff_key($compactVars, $vars);
            $missingVars && CompactCall::warn($absPath, $methodBody[$pp][2], $missingVars);
        }
    }

    private function collectSignatureVars($tokens, $i)
    {
        [, $signatures,$conditionCloseIndex] = Ifs::readCondition($tokens, $i);
        [$nextToken, $startNextToken] = TokenManager::getNextToken($tokens, $conditionCloseIndex);

        if ($nextToken[0] == T_USE) {
            [, $useBodySignatures,] = Ifs::readCondition($tokens, $startNextToken);

            $signatures = array_merge($useBodySignatures, $signatures);
        }

        $vars = [];
        foreach ($signatures as $sig) {
            ($sig[0] == T_VARIABLE) && $vars[$sig[1]] = null;
        }

        return $vars;
    }

    private function readMethodBodyAsTokens($tokens, $i)
    {
        // fast-forward to the start of function body
        [$char, $methodBodyStartIndex] = TokenManager::forwardTo($tokens, $i, ['{', ';']);

        // in order to avoid checking abstract methods (with no body) and do/while
        if ($char === ';') {
            return false;
        }

        try {
            // fast-forward to the end of function body
            [$methodBody,] = TokenManager::readBody($tokens, $methodBodyStartIndex);

            return $methodBody;
        } catch (Exception $e) {
            return false;
        }
    }
}
