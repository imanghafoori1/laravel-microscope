<?php

namespace Imanghafoori\LaravelMicroscope\Foundations\Iterators;

use Imanghafoori\LaravelMicroscope\Foundations\FileReaders\PhpFinder;
use Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\CheckCollection;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PathFilterDTO;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Throwable;

class CheckSet
{
    use FiltersFiles;

    public $pathDTO;

    /**
     * @var \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\CheckCollection
     */
    public $checks;

    private $namespace;

    private $path;

    /**
     * @var \Throwable[]
     */
    public $exceptions = [];

    public static $options;

    public static function initParams($checks, $options)
    {
        return CheckSet::init($checks, PathFilterDTO::makeFromOption($options));
    }

    public static function initParam($checks)
    {
        $pathDTO = PathFilterDTO::makeFromOption(self::$options);

        return CheckSet::init($checks, $pathDTO);
    }

    public static function init($checks, ?PathFilterDTO $pathDTO = null): CheckSet
    {
        $pathDTO && $pathDTO->includeFile && PhpFinder::$fileName = $pathDTO->includeFile;

        $obj = new self;
        $obj->setChecks($checks);
        $obj->pathDTO = $pathDTO;

        return $obj;
    }

    public function setChecks(array $checks)
    {
        $this->checks = CheckCollection::make($checks);
    }

    /**
     * @param  string  $psr4Namespace
     * @param  string  $psr4Path
     * @return int
     */
    public function applyChecksInPath($psr4Namespace, $psr4Path)
    {
        $this->namespace = $psr4Namespace;
        $this->path = $psr4Path;

        $finder = PhpFinder::getAllPhpFiles($psr4Path);
        $this->pathDTO && $finder = self::filterFiles($finder, $this->pathDTO);

        $filesCount = Loop::walkCount($finder, fn ($fileObj) => $this->applyChecks($fileObj));
        ChecksOnPsr4Classes::$checkedFilesCount += $filesCount;

        return $filesCount;
    }

    /**
     * @param  \Symfony\Component\Finder\SplFileInfo  $phpFileObj
     * @return void
     */
    private function applyChecks($phpFileObj)
    {
        $absFilePath = $phpFileObj->getRealPath();

        $file = PhpFileDescriptor::make($absFilePath);

        Loop::over(
            $this->checks->checks,
            fn ($check) => $this->applyCheck($check, $file)
        );

        return true;
    }

    private function performCheck($check, PhpFileDescriptor $file)
    {
        if (is_string($check)) {
            /**
             * @var $check class-string<\Imanghafoori\LaravelMicroscope\Check>
             */
            return $check::check($file, $this->path, $this->namespace);
        }

        return $check->check($file, $this->path, $this->namespace);
    }

    private function applyCheck($check, PhpFileDescriptor $file): void
    {
        try {
            $newTokens = $this->performCheck($check, $file);
            $newTokens && $file->setTokens($newTokens);
        } catch (Throwable $exception) {
            $this->exceptions[] = $exception;
        }
    }
}
