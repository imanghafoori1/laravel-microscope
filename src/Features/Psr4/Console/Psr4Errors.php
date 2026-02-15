<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\Console;

use Imanghafoori\LaravelMicroscope\Features\Psr4\Console\NamespaceFixer\NamespaceFixerMessages;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;

class Psr4Errors
{
    public static function handle(array $errorsLists, $command)
    {
        AskAndFixNamespace::$command = $command;

        Loop::deepOver($errorsLists, fn ($error) => self::handleError($error));
    }

    private static function handleError($error)
    {
        if ($error->errorType() === 'namespace') {
            AskAndFixNamespace::handle($error);
        } elseif ($error->errorType() === 'filename') {
            self::wrongFileName($error->entity);
        }
    }

    private static function wrongFileName($error)
    {
        NamespaceFixerMessages::wrongFileName(
            $error['relativePath'],
            $error['class'],
            $error['fileName']
        );
    }
}
