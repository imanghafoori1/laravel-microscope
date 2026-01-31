<?php

namespace Imanghafoori\LaravelMicroscope\Features\FacadeAlias;

class FacadeAliasMessages
{
    public static function askReplace($base, $aliases): string
    {
        return "Do you want to replace $base with $aliases";
    }

    public static function atLine($relativePath, $line): string
    {
        return "at $relativePath:$line";
    }
}
