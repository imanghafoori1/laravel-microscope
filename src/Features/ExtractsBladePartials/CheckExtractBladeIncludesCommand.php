<?php

namespace Imanghafoori\LaravelMicroscope\Features\ExtractsBladePartials;

use Imanghafoori\LaravelMicroscope\Foundations\BaseCommand;

class CheckExtractBladeIncludesCommand extends BaseCommand
{
    protected $signature = 'check:extract_blades';

    protected $description = 'Checks to extract blade partials';

    public $initialMsg = 'Checking to extract blade partials...';

    public $gitConfirm = true;

    public $checks = [ExtractBladePartial::class];

    public $customMsg = 'Blade files extracted.';

    public function handleCommand()
    {
        $this->printAll(PHP_EOL.$this->forBladeFiles());
    }
}
