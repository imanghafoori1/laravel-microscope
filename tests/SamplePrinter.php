<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

class SamplePrinter
{
    public function writeln($msg)
    {
        $_SESSION['writeln'][] = $msg;
    }

    public function confirm($msg)
    {
        $_SESSION['confirm'][] = $msg;

        return true;
    }
}