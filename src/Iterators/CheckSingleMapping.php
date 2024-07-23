<?php

namespace Imanghafoori\LaravelMicroscope\Iterators;

use Imanghafoori\LaravelMicroscope\FileReaders\PhpFinder;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Throwable;

class CheckSingleMapping
{
    use FiltersFiles;

    public $includeFolder;

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

    public static function init($checks, $params, $includeFile, $includeFolder): CheckSingleMapping
    {
        $includeFile && PhpFinder::$fileName = $includeFile;

        $obj = new self;
        $obj->checks = $checks;
        $obj->params = $params;
        $obj->includeFolder = $includeFolder;

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
        $this->includeFolder && $finder = self::filterFiles($finder, $this->includeFolder);

        $filesCount = 0;
        foreach ($finder as $phpFilePath) {
            $filesCount++;
            $this->applyChecks($phpFilePath);
        }

        return $filesCount;
    }

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
