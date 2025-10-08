<?php

namespace Imanghafoori\LaravelMicroscope\Foundations;

use Illuminate\Console\Command;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\SearchReplace\CachedFiles;

class BaseCommand extends Command
{
    /**
     * @var ErrorPrinter
     */
    public $errorPrinter;

    public $options;

    public $checkSet;

    public $gitConfirm = false;

    public array $params = [];

    /**
     * @var \Imanghafoori\LaravelMicroscope\Foundations\BaseCommand
     */
    public $confirmer;

    public function handle()
    {
        $this->errorPrinter = ErrorPrinter::singleton();
        $this->errorPrinter->printer = $this->getOutput();
        $this->confirmer = $this->options = CheckSet::$options = $this;
        $this->checkSet = $this->getCheckSet();

        event('microscope.start.command');

        if (property_exists($this, 'initialMsg')) {
            $this->info($this->initialMsg);
        }

        $answer = $this->gitConfirm ? $this->gitConfirm() : true;
        if (! $answer) {
            return;
        }

        /*------------------------*/
        $this->handleCommand(new Iterator($this->checkSet, $this->getOutput()));
        /*------------------------*/
        CachedFiles::writeCacheFiles();

        if (! $this->errorPrinter->hasErrors()) {
            $this->info($this->customMsg ?? '');
        } else {
            $this->errorPrinter->logErrors();
        }

        $this->printTime();

        return $this->exitCode();
    }

    public function exitCode()
    {
        return $this->errorPrinter->hasErrors() ? 1 : 0;
    }

    public function printTime()
    {
        $this->errorPrinter->printTime();
    }

    public function gitConfirm()
    {
        $this->warn('This command is going to make changes to your files!');

        return $this->output->confirm('Do you have committed everything in git?', true);
    }

    protected function getCheckSet(): CheckSet
    {
        return CheckSet::initParam($this->checks);
    }

    public function output($output)
    {
        $this->output = $output;
    }

    public function input($input)
    {
        $this->input = $input;
    }
}
