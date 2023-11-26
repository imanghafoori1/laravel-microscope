<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4;

use Illuminate\Console\Command;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class Psr4Errors
{
    /**
     * @var Command
     */
    private static $command;

    public static function handle(array $errorsLists, Command $command)
    {
        self::$command = $command;
        $before = self::beforeReferenceFix();
        $after = self::afterReferenceFix();

        foreach ($errorsLists as $errors) {
            foreach ($errors as $wrong) {
                self::handleError($wrong, $before, $after);
            }
        }
    }

    private static function handleError($wrong, $beforeFix, $afterFix)
    {
        if ($wrong['type'] === 'namespace') {
            $absPath = $wrong['absFilePath'];
            $from = $wrong['currentNamespace'];
            $to = $wrong['correctNamespace'];
            $class = $wrong['class'];
            $relativePath = str_replace(base_path(), '', $absPath);

            CheckPsr4Printer::warnIncorrectNamespace($relativePath, $from, $class);

            if (CheckPsr4Printer::ask(self::$command, $to)) {
                self::updateOldRefs($absPath, $from, $to, $class, $beforeFix, $afterFix, $relativePath);
            }
        } elseif ($wrong['type'] === 'filename') {
            CheckPsr4Printer::wrongFileName($wrong['relativePath'], $wrong['class'], $wrong['fileName']);
        }
    }

    private static function afterReferenceFix()
    {
        return function ($path, $changedLineNums, $content) {
            Filesystem::$fileSystem::file_put_contents($path, $content);

            $p = ErrorPrinter::singleton();
            foreach ($changedLineNums as $line) {
                $p->simplePendError('', $path, $line, 'ns_replacement', 'Namespace replacement:');
            }
        };
    }

    private static function beforeReferenceFix()
    {
        $command = self::$command;
        if ($command->option('force-ref-fix')) {
            return function () {
                return true;
            };
        }

        return function ($path, $lineIndex, $lineContent) use ($command) {
            $command->getOutput()->writeln(ErrorPrinter::getLink($path, $lineIndex));
            $command->warn($lineContent);
            $msg = 'Do you want to update reference to the old namespace?';

            return $command->confirm($msg, true);
        };
    }

    private static function updateOldRefs($absPath, $from, $to, $class, $beforeFix, $afterFix, $relativePath)
    {
        NamespaceFixer::fix($absPath, $from, $to);
        $command = self::$command;

        if ($from && ! $command->option('no-ref-fix')) {
            $changes = [
                $from.'\\'.$class => $to.'\\'.$class,
            ];

            ClassRefCorrector::fixAllRefs($changes, FilePathsForReferenceFix::getFiles(), $beforeFix, $afterFix);
        }
        CheckPsr4Printer::fixedNamespace($relativePath, $from, $to);
    }
}
