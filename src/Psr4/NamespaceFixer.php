<?php

namespace Imanghafoori\LaravelMicroscope\Psr4;

use Imanghafoori\Filesystem\FileManipulator;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\SearchReplace\Searcher;

class NamespaceFixer
{
    public static function fix($absPath, $incorrectNamespace, $correctNamespace)
    {
        // decides to add namespace (in case there is no namespace) or edit the existing one.
        [$oldLine, $newline] = self::getNewLine($incorrectNamespace, $correctNamespace);
        $oldLine = \ltrim($oldLine, '\\');

        $tokens = token_get_all(file_get_contents($absPath));
        if ($oldLine !== '<?php') {
            // replacement
            [$newVersion, $lines] = Searcher::searchReplace([
                'fix' => [
                    'search' => 'namespace '.$oldLine.';',
                    'replace' => 'namespace '.$newline.';',
                ],
            ], $tokens);
            Filesystem::$fileSystem::file_put_contents($absPath, $newVersion);
        } elseif ($tokens[2][0] !== T_DECLARE) {
            // insertion
            FileManipulator::replaceFirst($absPath, $oldLine, '<?php'.PHP_EOL.PHP_EOL.$newline);
        } else {
            // inserts after declare
            $i = 2;
            while ($tokens[$i++] !== ';') {
            }
            FileManipulator::insertNewLine($absPath, PHP_EOL.$newline, $tokens[$i][2] + 1);
        }
    }

    private static function getNewLine($incorrectNamespace, $correctNamespace)
    {
        if ($incorrectNamespace) {
            return [$incorrectNamespace, $correctNamespace];
        }

        // In case there is no namespace specified in the file:
        return ['<?php', 'namespace '.$correctNamespace.';'.PHP_EOL];
    }
}
