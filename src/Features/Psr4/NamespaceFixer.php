<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4;

use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\SearchReplace\Searcher;

class NamespaceFixer
{
    public static function fix(PhpFileDescriptor $file, $incorrectNamespace, $correctNamespace)
    {
        // decides to add namespace (in case there is no namespace) or edit the existing one.
        [$oldLine, $newline] = self::getNewLine($incorrectNamespace, $correctNamespace);
        $oldLine = \ltrim($oldLine, '\\');

        $tokens = $file->getTokens();
        if ($oldLine !== '<?php') {
            // replacement
            [$newVersion, $lines] = Searcher::searchReplace(self::getPattern($oldLine, $newline), $tokens);
            $file->putContents($newVersion);
        } elseif ($tokens[2][0] !== T_DECLARE) {
            // insertion
            $file->replaceFirst($oldLine, '<?php'.PHP_EOL.PHP_EOL.$newline);
        } else {
            // inserts after declare
            $i = 2;
            while ($tokens[$i] !== ';') {
                $i++;
            }
            while (! isset($tokens[$i][2])) {
                $i++;
            }
            $file->insertNewLine(PHP_EOL.$newline, $tokens[$i][2] + 1);
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

    private static function getPattern(string $oldLine, $newline): array
    {
        return [
            'fix' => [
                'search' => 'namespace '.$oldLine.';',
                'replace' => 'namespace '.$newline.';',
            ],
        ];
    }
}
