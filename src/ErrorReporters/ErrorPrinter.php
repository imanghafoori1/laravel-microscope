<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class ErrorPrinter
{
    public static $ignored;

    /**
     * @var array
     */
    public $errorsList = [];

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
     * @var positive-int
     */
    public static $terminalWidth = 100;

    /**
     * @return self
     */
    public static function singleton($output = null)
    {
        is_null(self::$instance) && (self::$instance = new self);

        $output && (self::$instance->printer = $output);

        return self::$instance;
    }

    public function addPendingError($path, $lineNumber, $key, $header, $errorData)
    {
        if (self::isIgnored($path)) {
            return;
        }
        $this->count++;
        $this->errorsList[$key][] = (new PendingError())
            ->header($header)
            ->errorData($errorData)
            ->link($path, $lineNumber);
    }

    public function simplePendError($text, $absPath, $lineNumber, $key, $header, $rest = '', $pre = '')
    {
        is_a($absPath, PhpFileDescriptor::class) && ($absPath = $absPath->getAbsolutePath());

        $errorData = $pre.Color::blue($text).$rest;

        $this->addPendingError($absPath, $lineNumber, $key, $header, $errorData);
    }

    public function print($msg, $path = '   ')
    {
        $this->printer->writeln($path.$msg);
    }

    public function printHeader($msg, $counted = true)
    {
        if ($counted) {
            $number = ++$this->total;
            ($number < 10) && $number = " $number";
            $number = Color::cyan($number);
            $path = "  $number ";
        } else {
            $path = '';
        }

        $width = ErrorPrinter::$terminalWidth - 6;
        PendingError::$maxLength = max(PendingError::$maxLength, strlen($msg), $width);
        PendingError::$maxLength = min(PendingError::$maxLength, $width);
        $this->print(Color::red($msg), $path);
    }

    public function end()
    {
        $line = str_repeat('_', 3 + PendingError::$maxLength);
        $this->printer->writeln(Color::gray($line));
    }

    public function printLink($file, $lineNumber = 4)
    {
        $this->print(self::getLink($file->relativePath(), $lineNumber), '');
    }

    public static function getLink($path, $lineNumber = 4)
    {
        $relativePath = FilePath::normalize(trim($path, '\\/'));

        return 'at '.Color::green($relativePath).':'.Color::green($lineNumber);
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
        Loop::deepOver($this->errorsList, fn ($error) => $this->printError($error));

        foreach ($this->pended as $pend) {
            $this->print($pend);
            $this->end();
        }
    }

    public function getCount($key)
    {
        return count($this->errorsList[$key] ?? []);
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

        return Loop::any(
            $ignorePatterns,
            fn ($pattern) => self::is(base_path($pattern), $path)
        );
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

        return " ⏰ Finished in: $duration (sec)";
    }

    public static function lineSeparator(): string
    {
        return ' '.Color::gray(str_repeat('_', ErrorPrinter::$terminalWidth - 3));
    }

    private function printError($error): void
    {
        $this->printHeader($error->getHeader());
        $this->print($error->getErrorData());
        $this->printLink(
            PhpFileDescriptor::make($error->getLinkPath()),
            $error->getLinkLineNumber()
        );
        $this->end();
    }
}
