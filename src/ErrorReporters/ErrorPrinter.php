<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Exception;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Symfony\Component\Console\Terminal;

class ErrorPrinter
{
    public static $ignored;

    /**
     * @var array
     */
    public $errorsList = [];

    /**
     * @var array
     */
    public $errorsCounts = [
        'extraWrongImport' => 0,
        'wrongClassRef' => 0,
        'extraCorrectImport' => 0,
    ];

    /**
     * @var int
     */
    public $total = 0;

    /**
     * The output interface implementation.
     *
     * @var \Illuminate\Console\OutputStyle
     */
    public $printer;

    /**
     * @var bool
     */
    public $logErrors = true;

    /**
     * @var string[]
     */
    public $pended = [];

    /**
     * @var int
     */
    public $count = 0;

    /**
     * @var self
     */
    public static $instance;

    /**
     * @var string
     */
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

    public function flushErrors()
    {
        if ($this->hasErrors()) {
            $this->logErrors();
            foreach (['extraWrongImport', 'wrongClassRef', 'extraCorrectImport'] as $item) {
                $this->errorsCounts[$item] += count($this->errorsList[$item] ?? []);
            }
            $this->errorsList = [];
            $this->count = 0;
        }
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

    public function color($msg, $color = 'blue')
    {
        return "<fg=$color>$msg</>";
    }

    public function print($msg, $path = '   ')
    {
        $this->printer->writeln($path.$msg);
    }

    public function printHeader($msg)
    {
        $number = ++$this->total;
        ($number < 10) && $number = " $number";

        $number = $this->color($number, 'cyan');
        $path = "  $number ";

        $width = (new Terminal)->getWidth() - 6;
        PendingError::$maxLength = max(PendingError::$maxLength, strlen($msg), $width);
        PendingError::$maxLength = min(PendingError::$maxLength, $width);
        $this->print($this->color($msg, 'red'), $path);
    }

    public function end()
    {
        $line = function ($color) {
            $this->printer->writeln(' <fg='.$color.'>'.str_repeat('_', 3 + PendingError::$maxLength).'</> ');
        };
        try {
            $line('gray');
        } catch (Exception $e) {
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
     * @return bool
     */
    public function hasErrors()
    {
        return $this->count > 0;
    }

    public function logErrors()
    {
        foreach ($this->errorsList as $list) {
            foreach ($list as $error) {
                $this->printHeader($error->getHeader());
                $this->print($error->getErrorData());
                $this->printLink(
                    $error->getLinkPath(),
                    $error->getLinkLineNumber()
                );
                $this->end();
            }
        }

        foreach ($this->pended as $pend) {
            $this->print($pend);
            $this->end();
        }
    }

    public function getCount($key)
    {
        return \count($this->errorsList[$key] ?? []);
    }

    public function printTime()
    {
        $this->logErrors && $this->printer->writeln($this->getTimeMessage(), 2);
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

    private function getTimeMessage()
    {
        $duration = microtime(true) - microscope_start;
        $duration = round($duration, 3);

        return "time: {$duration} (sec)";
    }

    public static function lineSeparator(): string
    {
        return ' <fg='.config('microscope.colors.line_separator').'>'.str_repeat('_', (new Terminal)->getWidth() - 3).'</>';
    }
}
