<?php

namespace Imanghafoori\LaravelMicroscope\Features\FacadeAlias;

use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
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

    public static function handle(PhpFileDescriptor $file, $usageInfo, $base, $alias, $tokens, $imports)
    {
        if (self::$forceReplace || self::ask($file, $usageInfo, $base, $alias)) {
            $newVersion = self::searchReplace($usageInfo[0], $alias, $tokens, $base, $imports);

            $file->putContents(Refactor::toString($newVersion));

            $tokens = $file->getTokens(true);

            self::$replacementsCount++;
        }

        return $tokens;
    }

    private static function ask(PhpFileDescriptor $file, $use, $base, $aliases)
    {
        $relativePath = FilePath::normalize($file->relativePath());
        self::$command->writeln('at '.$relativePath.':'.$use[1]);
        $question = 'Do you want to replace <fg=yellow>'.$base.'</> with <fg=yellow>'.$aliases.'</>';

        return self::$command->confirm($question, true);
    }

    private static function searchReplace($base, $aliases, $tokens, $as, $imports)
    {
        if (self::isAlreadyImported($imports, $aliases)) {
            return self::replaceWithAs($base, $aliases, $tokens);
        }

        if (self::needsAlias($base, $aliases, $as)) {
            return self::replaceWithAs($base, $aliases, $tokens);
        }

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
                ], $tokens, 1);
        }

        return $newVersion;
    }

    private static function isAlreadyImported($imports, $alias)
    {
        return isset($imports[class_basename($alias)]) && $imports[class_basename($alias)][0] === $alias;
    }

    private static function replaceWithAs($base, $aliases, $tokens)
    {
        [$newVersion, $lines] = Searcher::search(
            [
                [
                    'search' => 'use '.$base.';',
                    'replace' => 'use '.ltrim($aliases).' as '.$base.';',
                ],
            ], $tokens, 1
        );

        if (! $lines) {
            [$newVersion, $lines] = Searcher::search(
                [
                    [
                        'search' => 'use \\'.$base.';',
                        'replace' => 'use '.ltrim($aliases).' as '.$base.';',
                    ],
                ], $tokens, 1
            );
        }

        return $newVersion;
    }

    private static function needsAlias($base, $aliases, $as)
    {
        return $base !== class_basename($aliases) && $base === $as;
    }
}
