<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Imanghafoori\LaravelMicroscope\Analyzers\ParseUseStatement;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class CheckClassReferences
{
    public function check($tokens, $blade)
    {
        $classes = ParseUseStatement::findClassReferences($tokens, $blade->getPathname());

        foreach ($classes as $class) {
            if (! $this->exists($class['class'])) {
                app(ErrorPrinter::class)->bladeImport($class, $blade);
            }
        }
    }

    private function exists($class)
    {
        return class_exists($class) || interface_exists($class);
    }
}
