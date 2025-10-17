<?php

namespace Imanghafoori\LaravelMicroscope\Tests\ForFolderPaths;

use Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\LaravelFoldersReport;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ReportPrinter;
use Imanghafoori\LaravelMicroscope\Iterators\CheckSet;
use Imanghafoori\LaravelMicroscope\Iterators\ForFolderPaths;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use PHPUnit\Framework\TestCase;

class ForFolderPathsCheckTest extends TestCase
{
    public function test_basic()
    {
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

        $this->assertEquals([
            __DIR__.DIRECTORY_SEPARATOR.'ForFolderPathsCheckTest.php',
            __DIR__.DIRECTORY_SEPARATOR.'SampleCheck.php',
        ], $_SESSION['files']);
    }

    private function getDirsList()
    {
        return [__DIR__];
    }
}
