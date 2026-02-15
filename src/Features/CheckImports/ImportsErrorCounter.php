<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use Imanghafoori\LaravelMicroscope\Features\CheckImports\Handlers\ExtraWrongImportsHandler;
use JetBrains\PhpStorm\Pure;

class ImportsErrorCounter
{
    /**
     * @var array<string, int>
     */
    public $errors = [];

    public static function calculateErrors($errors)
    {
        $self = new self;

        $self->errors['extraWrongImport'] = ExtraWrongImportsHandler::$errorCount;
        $self->errors['wrongClassRef'] = count($errors['wrongClassRef'] ?? []);
        $self->errors['wrongMethod'] = count($errors['wrongMethodError'] ?? []);
        $self->errors['wrongStringyClass'] = count($errors['wrongUsedClassError'] ?? []);

        ExtraWrongImportsHandler::$errorCount = 0;

        return $self;
    }

    #[Pure(true)]
    public function getExtraWrongCount(): int
    {
        return $this->getCount('extraWrongImport');
    }

    #[Pure(true)]
    public function getWrongUsedClassCount(): int
    {
        return $this->getCount('wrongClassRef');
    }

    #[Pure(true)]
    public function getTotalErrors(): int
    {
        return $this->getExtraWrongCount() +
            $this->getWrongUsedClassCount() +
            $this->errors['wrongMethod'] +
            $this->errors['wrongStringyClass'];
    }

    #[Pure(true)]
    private function getCount(string $key)
    {
        return $this->errors[$key] ?? 0;
    }
}
