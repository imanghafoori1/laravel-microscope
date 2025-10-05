<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\FileReaders\PhpFinder;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\Iterators\DTO\CheckCollection;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Throwable;

class CheckSet
{
    use FiltersFiles;

    public $pathDTO;

    public $params;

    /**
     * @var \Imanghafoori\LaravelMicroscope\Iterators\DTO\CheckCollection
     */
    public $checks;

    private $namespace;

    private $path;

    /**
     * @var \Throwable[]
     */
    public $exceptions = [];

    public static $options;

    public static function initParams($checks, $command, $params = [])
    {
        return CheckSet::init($checks, PathFilterDTO::makeFromOption($command), $params);
    }

    public static function initParam($checks, $params = [])
    {
        $pathDTO = PathFilterDTO::makeFromOption(self::$options);

        return CheckSet::init($checks, $pathDTO, $params);
    }

    public static function init($checks, PathFilterDTO $pathDTO = null, $params = []): CheckSet
    {
        $pathDTO->includeFile && PhpFinder::$fileName = $pathDTO->includeFile;

        $obj = new self;
        $obj->checks = CheckCollection::make($checks);
        $obj->params = $params;
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

        $filesCount = 0;
        foreach ($finder as $phpFilePath) {
            $filesCount++;
            $this->applyChecks($phpFilePath);
        }
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

        $params = $this->params;

        foreach ($this->checks->checks as $check) {
            try {
                /**
                 * @var $check class-string<\Imanghafoori\LaravelMicroscope\Check>
                 */
                $newTokens = $check::check($file, $params, $this->path, $this->namespace);
                if ($newTokens) {
                    $file->setTokens($newTokens);
                    $params = $this->params;
                }
            } catch (Throwable $exception) {
                $this->exceptions[] = $exception;
            }
        }
    }
}
