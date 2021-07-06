<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use App\Finder;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\Analyzers\FileManipulator;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class Refactorings
{
    public static function check($tokens, $absFilePath, $classFilePath, $psr4Path, $psr4Namespace, $params)
    {
        //$psr4 = ComposerJson::readAutoload();
        //$namespaces = array_keys($psr4);
        //$errorPrinter = resolve(ErrorPrinter::class);

        [$tokens_to_find, $replacement, $placeholders] = $params;

        foreach ($tokens_to_find as $i => $token_to_find) {
            foreach ($tokens as $t) {
                if (self::match($t, $token_to_find)) {
                }
            }

            self::sequence_in_array($token_to_find, $tokens) && dd($absFilePath);
        }
    }

    private static function match($t, $token_to_find): bool
    {
        return $t == $token_to_find;
    }

    public static function sequence_in_array(array $needle, array $haystack)
    {
        $haystackCount = count($haystack);
        $needleCount = count($needle);

        if ($needleCount > $haystack) {
            throw new InvalidArgumentException('$needle array must be smaller than $haystack array.');
        }

        for ($i = 0; $i <= $haystackCount - $needleCount; $i++) {
            $matchCount = 0;
            for ($j = 0; $j < $needleCount; $j++) {
                if ($needle[$j] == $haystack[$i + $j]) {
                    $matchCount++;
                    if ($matchCount == $needleCount) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
