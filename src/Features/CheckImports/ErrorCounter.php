<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckImports;

class ErrorCounter
{
    /**
     * @var array
     */
    protected $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
    }

    public function getWrongCount(): int
    {
        return count($this->errors['extraWrongImport'] ?? []);
    }

    public function getWrongUsedClassCount(): int
    {
        return count($this->errors['wrongClassRef'] ?? []);
    }

    public function getExtraImportsCount(): int
    {
        return count($this->errors['extraCorrectImport'] ?? []) + $this->getWrongCount();
    }

    public function getTotalErrors(): int
    {
        return $this->getWrongCount() + $this->getWrongUsedClassCount() + $this->getExtraImportsCount();
    }
}
