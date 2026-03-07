<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckGenericDocBlocks;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Features\CheckDeadControllers\DeadControllerActions;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\Console;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\Str;

class GenericDocblocks implements Check
{
    const statements = [
        '* Display a listing of the resource.',
        '* Show the form for creating a new resource.',
        '* Store a newly created resource in storage.',
        '* Display the specified resource.',
        '* Show the form for editing the specified resource.',
        '* Update the specified resource in storage.',
        '* Remove the specified resource from storage.',
        '* Handle the incoming request.',
    ];

    public static $foundCount = 0;

    public static function check(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();

        $fullNamespace = $file->getNamespace();

        if (! DeadControllerActions::isLaravelController($fullNamespace)) {
            return null;
        }

        [$tokens, $removedToken] = self::removeDocBlocks(
            self::getDocblockIndexes($tokens),
            $tokens
        );

        if ($removedToken && Console::confirm(self::getQuestion($file))) {
            GenericDocblocksHandler::handle($file, $removedToken);
            $file->saveTokens($tokens);
        }
    }

    private static function getQuestion(PhpFileDescriptor $file)
    {
        return 'Do you want to remove doc-blocks from: '.Color::yellow($file->getFileName());
    }

    private static function removeDocblock($tokens, $i)
    {
        unset($tokens[$i]);
        if (self::surroundedByWhitespace($tokens, $i)) {
            unset($tokens[$i + 1]);
        }

        return $tokens;
    }

    private static function surroundedByWhitespace($tokens, $i)
    {
        return ($tokens[$i - 1][0] ?? 0) === T_WHITESPACE && ($tokens[$i + 1][0] ?? 0) === T_WHITESPACE;
    }

    private static function shouldBeRemoved($docblock)
    {
        return Str::contains($docblock, self::statements);
    }

    private static function removeDocBlocks($indexes, $tokens): array
    {
        foreach ($indexes as $i) {
            self::$foundCount++;
            $doc = $tokens[$i];
            $tokens = self::removeDocblock($tokens, $i);
        }

        return [$tokens, $doc ?? null];
    }

    private static function getDocblockIndexes(array $tokens)
    {
        foreach ($tokens as $i => $token) {
            if ($token[0] !== T_DOC_COMMENT) {
                continue;
            }

            if (! self::shouldBeRemoved($token[1])) {
                continue;
            }

            yield $i;
        }
    }
}
