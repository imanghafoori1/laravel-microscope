<?php

namespace Imanghafoori\LaravelMicroscope\Contracts;

interface FileCheckContract
{
    function onFileTouch($file);
}
