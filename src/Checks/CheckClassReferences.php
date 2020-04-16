<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Imanghafoori\LaravelMicroscope\Analyzers\ParseUseStatement;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckClassReferences
{
    public function check($tokens, $pathName)
    {
        $classes = ParseUseStatement::findClassReferences($tokens, $pathName);

        foreach ($classes as $class) {
            if (! $this->exists($class['class'])) {
                app(ErrorPrinter::class)->bladeImport($class, $pathName);
            }
        }
    }

    private function exists($class)
    {
        return class_exists($class) || interface_exists($class);
    }
}
