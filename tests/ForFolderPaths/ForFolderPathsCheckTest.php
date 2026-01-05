<?php

namespace Imanghafoori\LaravelMicroscope\Tests\ForFolderPaths;

use Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\LaravelFoldersReport;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ReportPrinter;
use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\ForFolderPaths;
use Imanghafoori\LaravelMicroscope\Foundations\PathFilterDTO;
use PHPUnit\Framework\TestCase;

class ForFolderPathsCheckTest extends TestCase
{
    public function test_basic()
    {
        BasePath::$path = __DIR__;
        $pathDTO = PathFilterDTO::make();
        $checkSet = CheckSet::init([SampleCheck::class], $pathDTO);
        $foldersStats = ForFolderPaths::check($checkSet, ['config' => $this->getDirsList()]);
        $lines = LaravelFoldersReport::formatFoldersStats($foldersStats);

        $_SESSION['test_ms'] = [];
        $_SESSION['files'] = [];
        ReportPrinter::printAll($lines, new class
        {
            public function write($msg)
            {
                $_SESSION['test_ms'][] = $msg;
            }

            public function writeln($msg)
            {
                $_SESSION['test_ms'][] = $msg;
            }
        });

        $this->assertTrue(array_key_exists(__DIR__.DIRECTORY_SEPARATOR.'ForFolderPathsCheckTest.php', $_SESSION['files']));
        $this->assertTrue(array_key_exists(__DIR__.DIRECTORY_SEPARATOR.'SampleCheck.php', $_SESSION['files']));
    }

    private function getDirsList()
    {
        return [__DIR__];
    }
}
