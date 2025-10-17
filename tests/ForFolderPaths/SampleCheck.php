<?php

namespace Imanghafoori\LaravelMicroscope\Tests\ForFolderPaths;

use Imanghafoori\LaravelMicroscope\Check;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class SampleCheck implements Check
{
    public static function check(PhpFileDescriptor $file)
    {
        $_SESSION['files'][$file->getAbsolutePath()] = $file->getAbsolutePath();
    }
}
