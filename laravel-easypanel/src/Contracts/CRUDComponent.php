<?php

namespace EasyPanel\Contracts;

interface CRUDComponent
{
    public function getModel();
    public function fields();
    public function inputs();
    public function validationRules();
    public function storePaths();
    public function searchable();
}
