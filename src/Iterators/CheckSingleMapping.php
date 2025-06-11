<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\FileReaders\PhpFinder;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use Throwable;

class CheckSingleMapping
{
    use FiltersFiles;

    public $pathDTO;

    public $params;

    /**
     * @var array<class-string<\Imanghafoori\LaravelMicroscope\Iterators\Check>>
     */
    public $checks;

    private $namespace;

    private $path;

    /**
     * @var \Throwable[]
     */
    public $exceptions = [];

    public static function init($checks, $params, PathFilterDTO $pathDTO): CheckSingleMapping
    {
        $pathDTO->includeFile && PhpFinder::$fileName = $pathDTO->includeFile;

        $obj = new self;
        $obj->checks = $checks;
        $obj->params = $params;
        $obj->pathDTO = $pathDTO;

        return $obj;
    }

    /**
     * @param  string  $psr4Namespace
     * @param  string  $psr4Path
     * @return int
     */
    public function applyChecksInPath($psr4Namespace, $psr4Path): int
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

        $processedParams = $this->getParams($file);

        foreach ($this->checks as $check) {
            try {
                /**
                 * @var $check \Imanghafoori\LaravelMicroscope\Iterators\Check
                 */
                $newTokens = $check::check($file, $processedParams, $this->path, $this->namespace);
                if ($newTokens) {
                    $file->setTokens($newTokens);
                    $processedParams = $this->getParams($file);
                }
            } catch (Throwable $exception) {
                $this->exceptions[] = $exception;
            }
        }
    }

    private function getParams(PhpFileDescriptor $file)
    {
        $params = $this->params;

        return (! is_array($params) && is_callable($params)) ? $params($file, $this->path, $this->namespace) : $params;
    }
}
