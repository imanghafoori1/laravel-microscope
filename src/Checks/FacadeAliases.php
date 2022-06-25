<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Foundation\AliasLoader;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\SearchReplace\Searcher;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;
use Imanghafoori\TokenAnalyzer\Refactor;

class FacadeAliases
{
    public static $command;

    public static function check($tokens, $absFilePath, $classFilePath, $psr4Path, $psr4Namespace)
    {
        $aliases = AliasLoader::getInstance()->getAliases();
        $imports = ParseUseStatement::parseUseStatements($tokens);
        $imports = $imports[0] ?: [$imports[1]];
        $i = 0;

        foreach ($imports as $import) {
            foreach ($import as $base => $use) {
                if (! isset($aliases[$use[0]])) {
                    continue;
                }
                $relativePath = FilePath::normalize(\trim(\str_replace(base_path(), '', $absFilePath), '\\/'));
                self::$command->getOutput()->writeln('at '.$relativePath.':'.$use[1]);
                $result = self::$command->confirm('Do you want to replace <fg=yellow>'.$base.'</> with <fg=yellow>'.$aliases[$use[0]].'</>');
                if (! $result) {
                    continue;
                }

                [$newVersion, $lines] = Searcher::searchReplace(
                    [
                        [
                            'search' => 'use '.$base.';',
                            'replace' => 'use '.ltrim($aliases[$use[0]]).';',
                        ],
                    ], $tokens
                );
                if (! $lines) {
                    [$newVersion, $lines] = Searcher::searchReplace(
                        [
                            [
                                'search' => 'use '.$base.',',
                                'replace' => 'use '.ltrim($aliases[$use[0]]).',',
                            ],
                            [
                                'search' => ','.$base.';',
                                'replace' => ', '.ltrim($aliases[$use[0]]).';',
                            ],
                        ], $tokens
                    );
                }

                if (! $lines) {
                    [$newVersion, $lines] = Searcher::searchReplace(
                        [
                            [
                                'search' => ','.$base.',',
                                'replace' => ', '.ltrim($aliases[$use[0]]).',',
                            ],
                        ], $tokens
                    );
                }

                Filesystem::$fileSystem::file_put_contents($absFilePath, $newVersion);
                $tokens = token_get_all(Filesystem::$fileSystem::file_get_contents($absFilePath));
            }
        }
    }
}