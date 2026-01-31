<?php

namespace Imanghafoori\LaravelMicroscope\Features\ActionComments;

use Imanghafoori\LaravelMicroscope\Foundations\Color;

class ActionCommentMsg
{
    public static function getQuestion($fullNamespace): string
    {
        return 'Add route definition into the: '.Color::yellow($fullNamespace);
    }
}
