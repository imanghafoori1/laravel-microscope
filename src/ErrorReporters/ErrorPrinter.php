<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Symfony\Component\Console\Terminal;

class ErrorPrinter
{
    public static $ignored;

    public $errorsList = [
        'total' => 0,
    ];

    public $printer;

    public $logErrors = true;

    public $pended = [];

    public $count = 0;

    public static $instance;

    public static $basePath;

    /**
     * @return self
     */
    public static function singleton($output = null)
    {
        if (! self::$instance) {
            self::$instance = new self;
        }
        $output && (self::$instance->printer = $output);

        return self::$instance;
    }

    public function authConf()
    {
        $this->print('The model in the "config/auth.php" is not a valid class');
    }

    public function badRelation($absPath, $lineNumber, $relatedModel)
    {
        $header = 'Wrong model is passed in relation:';

        $this->simplePendError($relatedModel, $absPath, $lineNumber, 'badRelation', $header);
    }

    public function wrongImport($absPath, $class, $lineNumber)
    {
        $this->simplePendError("use $class;", $absPath, $lineNumber, 'wrongImport', 'Wrong import:');
    }

    public function addPendingError($path, $lineNumber, $key, $header, $errorData)
    {
        if (self::isIgnored($path)) {
            return;
        }
        $this->count++;
        $this->errorsList[$key][] = (new PendingError($key))
            ->header($header)
            ->errorData($errorData)
            ->link($path, $lineNumber);
    }

    public function simplePendError($yellowText, $absPath, $lineNumber, $key, $header, $rest = '', $pre = '')
    {
        $errorData = $pre.$this->color($yellowText).$rest;

        $this->addPendingError($absPath, $lineNumber, $key, $header, $errorData);
    }

    public function extraImport($absPath, $class, $lineNumber)
    {
        $this->simplePendError($class, $absPath, $lineNumber, 'extraImport', 'Import is not used:');
    }

    public function color($msg)
    {
        return "<fg=blue>$msg</>";
    }

    public function print($msg, $path = '   ')
    {
        $this->printer->writeln($path.$msg);
    }

    public function printHeader($msg)
    {
        $number = ++$this->errorsList['total'];
        ($number < 10) && $number = " $number";

        $number = '<fg=cyan>'.$number.' </>';
        $path = "  $number";

        $width = (new Terminal)->getWidth() - 6;
        PendingError::$maxLength = max(PendingError::$maxLength, strlen($msg), $width);
        PendingError::$maxLength = min(PendingError::$maxLength, $width);
        $this->print('<fg=red>'.$msg.'</>', $path);
    }

    public function end()
    {
        $line = function ($color) {
            $this->printer->writeln(' <fg='.$color.'>'.str_repeat('_', 3 + PendingError::$maxLength).'</> ');
        };
        try {
            $line('gray');
        } catch (\Exception $e) {
            $line('blue'); // for older versions of laravel
        }
    }

    public function printLink($path, $lineNumber = 4)
    {
        if ($path) {
            $this->print(self::getLink(str_replace(base_path(), '', $path), $lineNumber), '');
        }
    }

    public static function getLink($path, $lineNumber = 4)
    {
        $relativePath = FilePath::normalize(trim($path, '\\/'));

        return 'at <fg=green>'.$relativePath.'</>'.':<fg=green>'.$lineNumber.'</>';
    }

    /**
     * Checks for errors for the run command.
     *
     * @return int
     */
    public function hasErrors()
    {
        $errorsCollection = collect($this->errorsList);

        return $errorsCollection->flatten()->filter(function ($action) {
            return $action instanceof PendingError;
        })->count();
    }

    public function logErrors()
    {
        $errList = $this->errorsList;
        unset($errList['total']);

        foreach ($errList as $list) {
            foreach ($list as $error) {
                if ($error instanceof PendingError) {
                    $this->printHeader($error->getHeader());
                    $this->print($error->getErrorData());
                    $this->printLink($error->getLinkPath(), $error->getLinkLineNumber());
                    $this->end();
                }
            }
        }

        /*collect($this->errorsList)->except('total')->flatten()->each(function ($error) {
            if ($error instanceof PendingError) {
                $this->printHeader($error->getHeader());
                $this->print($error->getErrorData());
                $this->printLink($error->getLinkPath(), $error->getLinkLineNumber());
                $this->end();
            }
        });*/

        foreach ($this->pended as $pend) {
            $this->print($pend);
            $this->end();
        }
    }

    private static function possibleFixMsg($pieces)
    {
        $fixes = \implode("\n - ", $pieces);
        $fixes && $fixes = "\n Possible fixes:\n - ".$fixes;

        return $fixes;
    }

    public function wrongImportPossibleFixes($absPath, $class, $line, $fixes)
    {
        $fixes = self::possibleFixMsg($fixes);
        $this->wrongUsedClassError($absPath, $class.' '.$fixes, $line);
    }

    public function getCount($key)
    {
        return \count($this->errorsList[$key] ?? []);
    }

    public function printTime()
    {
        $this->logErrors && $this->printer->writeln('time: '.round(microtime(true) - microscope_start, 3).' (sec)', 2);
    }

    public static function thanks($command)
    {
        $command->line(PHP_EOL.'<fg=blue>|-------------------------------------------------|</>');
        $command->line('<fg=blue>|-----------     Star Me On Github     -----------|</>');
        $command->line('<fg=blue>|-------------------------------------------------|</>');
        $command->line('<fg=blue>|  Hey man, if you have found microscope useful   |</>');
        $command->line('<fg=blue>|  Please consider giving it an star on github.   |</>');
        $command->line('<fg=blue>|  \(^_^)/    Regards, Iman Ghafoori    \(^_^)/   |</>');
        $command->line('<fg=blue>|-------------------------------------------------|</>');
        $command->line('https://github.com/imanghafoori1/microscope');
    }

    /**
     * Check given path should be ignored.
     *
     * @param  string  $path
     * @return bool
     */
    public static function isIgnored($path)
    {
        $ignorePatterns = self::$ignored;

        if (! $ignorePatterns || ! is_array($ignorePatterns)) {
            return false;
        }

        foreach ($ignorePatterns as $ignorePattern) {
            if (self::is(base_path($ignorePattern), $path)) {
                return true;
            }
        }

        return false;
    }

    private static function is($pattern, $value)
    {
        if (! is_iterable($pattern)) {
            $pattern = [$pattern];
        }

        foreach ($pattern as $pattern) {
            if ($pattern === $value) {
                return true;
            }

            $pattern = preg_quote($pattern, '#');

            // Asterisks are translated into zero-or-more regular expression wildcards
            // to make it convenient to check if the strings starts with the given
            // pattern such as "library/*", making any string check convenient.
            $pattern = str_replace('\*', '.*', $pattern);

            if (preg_match('#^'.$pattern.'\z#u', $value) === 1) {
                return true;
            }
        }

        return false;
    }
}
