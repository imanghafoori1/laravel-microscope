<?php

namespace Imanghafoori\LaravelMicroscope\Foundations;

use Imanghafoori\TokenAnalyzer\ParseUseStatement;
use JetBrains\PhpStorm\Pure;

class UseStatementParser
{
    #[Pure]
    public static function parse(PhpFileDescriptor $file)
    {
        $imports = ParseUseStatement::parseUseStatements($file->getTokens());

        return $imports[0] ?: [$imports[1]];
    }
}
