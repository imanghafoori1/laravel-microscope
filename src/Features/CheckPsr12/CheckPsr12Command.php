<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckPsr12;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class CheckPsr12Command extends BaseCommand
{
    protected $signature = 'check:psr12
    {--f|file=}
    {--d|folder=}
    {--F|except-file= : Comma seperated patterns for file names to exclude}
    {--D|except-folder= : Comma seperated patterns for folder names to exclude}
    ';

    protected $description = 'Applies psr-12 rules';

    public $customMsg = 'All the public methods are marked explicitly as public.';

    public $initialMsg = 'Psr-12 is on the table...';

    public $checks = [CurlyBracesCheck::class];

    public $gitConfirm = true;

    public function handleCommand()
    {
        $this->printAll(Psr4Report::formatAutoloads(
            $this->forPsr4(),
            $this->forClassmaps()
        ));
    }
}
