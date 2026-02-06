<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

use JetBrains\PhpStorm\Pure;

class ErrorCounter
{
    /**
     * @var array<string, array>
     */
    public $errors = [];

    public static function calculateErrors($errors)
    {
        $self = new self;

        $self->errors['extraWrongImport'] = count($errors['extraWrongImport'] ?? []);
        $self->errors['wrongClassRef'] = count($errors['wrongClassRef'] ?? []);

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
        return $this->getExtraWrongCount() + $this->getWrongUsedClassCount();
    }

    #[Pure(true)]
    private function getCount(string $key)
    {
        return $this->errors[$key] ?? 0;
    }
}
