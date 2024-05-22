<?php

namespace Imanghafoori\LaravelMicroscope\Foundations;

class Path
{
    private static $basePath;

    /**
     * @var string
     */
    private $path;

    public static function setBasePath($basePath)
    {
        $basePath = self::normalizeDirectorySeparator($basePath);
        $basePath = self::removeTrailingSlash($basePath);

        self::$basePath = $basePath;
    }

    public static function make($path): self
    {
        return new self($path);
    }

    private function __construct($path)
    {
        $path = self::normalizeDirectorySeparator($path);
        $path = self::removeTrailingSlash($path);

        $this->path = $path;
    }

    private static function normalizeDirectorySeparator($absolutePath): string
    {
        return str_replace('/\\', DIRECTORY_SEPARATOR, $absolutePath);
    }

    public function relativePath()
    {
        $relPath = str_replace(self::$basePath, '', $this->path);

        return self::make(trim($relPath, DIRECTORY_SEPARATOR));
    }

    private static function removeTrailingSlash($path): string
    {
        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    public function __toString()
    {
        return $this->path;
    }

    public function getWithUnixDirectorySeprator()
    {
        return str_replace('\\', '/', $this->path);
    }

    public function getWithWindowsDirectorySeprator()
    {
        return str_replace('/', '\\', $this->path);
    }
}
