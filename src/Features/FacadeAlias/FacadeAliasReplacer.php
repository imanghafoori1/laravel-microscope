<?php

namespace Imanghafoori\LaravelMicroscope\Features\FacadeAlias;

use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\SearchReplace\Searcher;
use Imanghafoori\TokenAnalyzer\Refactor;

class FacadeAliasReplacer
{
    /**
     * @var \Illuminate\Console\OutputStyle
     */
    public static $command;

    public static $forceReplace = false;

    public static $replacementsCount = 0;

    public static function handle($absFilePath, $usageInfo, $base, $alias, $tokens)
    {
        if (self::$forceReplace || self::ask($absFilePath, $usageInfo, $base, $alias)) {
            $newVersion = self::searchReplace($usageInfo[0], $alias, $tokens, $base);

            Filesystem::$fileSystem::file_put_contents($absFilePath, Refactor::toString($newVersion));

            $tokens = token_get_all(Filesystem::$fileSystem::file_get_contents($absFilePath));

            self::$replacementsCount++;
        }

        return $tokens;
    }

    private static function ask($absFilePath, $use, $base, $aliases)
    {
        $relativePath = FilePath::normalize(\trim(\str_replace(base_path(), '', $absFilePath), '\\/'));
        self::$command->writeln('at '.$relativePath.':'.$use[1]);
        $question = 'Do you want to replace <fg=yellow>'.$base.'</> with <fg=yellow>'.$aliases.'</>';

        return self::$command->confirm($question, true);
    }

    private static function searchReplace($base, $aliases, $tokens, $as)
    {
        [$newVersion, $lines] = Searcher::search(
            [
                [
                    'search' => 'use '.$base.';',
                    'replace' => 'use '.ltrim($aliases).';',
                ],
            ], $tokens, 1
        );

        if (! $lines) {
            [$newVersion, $lines] = Searcher::search(
                [
                    [
                        'search' => 'use \\'.$base.';',
                        'replace' => 'use '.ltrim($aliases).';',
                    ],
                ], $tokens, 1
            );
        }

        if (! $lines) {
            [$newVersion, $lines] = Searcher::search(
                [
                    [
                        'search' => 'use '.$base.' as '.$as,
                        'replace' => 'use '.ltrim($aliases).' as '.$as,
                    ],
                ], $tokens, 1
            );
        }

        if (! $lines) {
            [$newVersion, $lines] = Searcher::search(
                [
                    [
                        'search' => 'use '.$base.',',
                        'replace' => 'use '.ltrim($aliases).';'.PHP_EOL.'use ',
                    ],
                    [
                        'search' => 'use \\'.$base.',',
                        'replace' => 'use '.ltrim($aliases).';'.PHP_EOL.'use ',
                    ],
                ], $tokens, 1
            );
        }

        if (! $lines) {
            [$newVersion, $lines] = Searcher::search(
                [
                    [
                        'search' => ','.$base.';',
                        'replace' => ', '.ltrim($aliases).';',
                    ],
                    [
                        'search' => ',\\'.$base.';',
                        'replace' => ', '.ltrim($aliases).';',
                    ],
                ], $tokens, 1
            );
        }

        if (! $lines) {
            [$newVersion, $lines] = Searcher::search(
                [
                    [
                        'search' => ','.$base.',',
                        'replace' => '; '.PHP_EOL.'use '.ltrim($aliases).';'.PHP_EOL.'use ',
                    ],
                    [
                        'search' => ',\\'.$base.',',
                        'replace' => '; '.PHP_EOL.'use '.ltrim($aliases).';'.PHP_EOL.'use ',
                    ],
                ], $tokens, 1
            );
        }

        if (! $lines) {
            [$newVersion, $lines] = Searcher::search(
                [
                    [
                        'search' => 'use \\'.$base.' as '.$as,
                        'replace' => 'use '.ltrim($aliases).' as '.$as,
                    ],
                ], $tokens, 1
            );
        }

        return $newVersion;
    }
}
