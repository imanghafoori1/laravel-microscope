<?php

namespace Imanghafoori\LaravelMicroscope\ErrorTypes;

class BladeFile
{
    public $data;

    public static function isMissing($absPath, $lineNumber, $viewName)
    {
        $e = new self;
        $e->data = compact('absPath', 'lineNumber', 'viewName');
        event($e);
    }
}
