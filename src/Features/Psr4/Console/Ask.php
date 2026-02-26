<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\Console;

use Imanghafoori\LaravelMicroscope\Features\Psr4\Console\NamespaceFixer\NamespaceFixerMessages;
use Imanghafoori\LaravelMicroscope\Foundations\Console;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class Ask
{
    public static $confirm = Confirm::class;

    public static function getAnswer(PhpFileDescriptor $file, $from, $class, $to, $option)
    {
        if ($option->noFix) {
            $answer = false;
        } elseif ($option->force) {
            $answer = true;
        } else {
            NamespaceFixerMessages::warnIncorrectNamespace($file, $from, $class);
            $answer = self::$confirm::ask($to);
            Console::deleteLine(9);
        }

        return $answer;
    }
}
