<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckGenericDocBlocks;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Features\CheckDeadControllers\RoutelessControllerActions;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\TokenAnalyzer\Refactor;
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

    public static $conformer;

    public static $foundCount = 0;

    public static $removedCount = 0;

    public static $controllers = [];

    public static function check(PhpFileDescriptor $file)
    {
        $tokens = $file->getTokens();

        $fullNamespace = $file->getNamespace();

        if (! RoutelessControllerActions::isLaravelController($fullNamespace)) {
            return null;
        }

        [$hasReplacement, $tokens, $token] = self::removeDocBlocks($tokens);

        $absFilePath = $file->getAbsolutePath();
        if ($hasReplacement && (self::$conformer)($absFilePath)) {
            ErrorPrinter::singleton()->addPendingError(
                $absFilePath, ($token[2] ?? 5) - 4, 'generic_docs', 'Docblock removed:', $token[1] ?? ''
            );
            Refactor::saveTokens($absFilePath, $tokens);
        }
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

    private static function removeDocBlocks(array $tokens): array
    {
        $hasReplacement = false;
        $doc = [];
        foreach ($tokens as $i => $token) {
            if ($token[0] !== T_DOC_COMMENT) {
                continue;
            }

            if (self::shouldBeRemoved($token[1])) {
                $doc = $token;
                self::$foundCount++;
                $hasReplacement = true;
                $tokens = self::removeDocblock($tokens, $i);
            }
        }

        return [$hasReplacement, $tokens, $doc];
    }
}
