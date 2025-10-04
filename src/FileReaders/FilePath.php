<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\PathFilterDTO;
use JetBrains\PhpStorm\Pure;

class FilePath
{
    /**
     * Normalize file path to standard formal
     * For a path like: "/usr/laravel/app\Http\..\..\database" returns "/usr/laravel/database".
     *
     * @param  string  $path  directory path
     * @return string
     */
    #[Pure]
    public static function normalize($path)
    {
        $dir = str_replace(['\\', '/', '//', '\\\\'], DIRECTORY_SEPARATOR, $path);

        $sections = explode(DIRECTORY_SEPARATOR, $dir);

        $result = [];
        foreach ($sections as $section) {
            if ($section == '..') {
                array_pop($result);
            } else {
                $result[] = $section;
            }
        }

        return implode(DIRECTORY_SEPARATOR, $result);
    }

    /**
     * get relative path. removes base path of laravel installation from an absolute path.
     *
     * @param  string  $absFilePath  Absolute directory path
     * @return string
     */
    #[Pure]
    public static function getRelativePath($absFilePath)
    {
        return trim(str_replace(BasePath::$path, '', $absFilePath), '/\\');
    }

    #[Pure]
    public static function getFolderFile($absFilePath): array
    {
        $segments = explode('/', str_replace('\\', '/', self::getRelativePath($absFilePath)));
        $fileName = array_pop($segments);

        return [$fileName, implode('/', $segments)];
    }

    /**
     * @param  $filePath
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return bool
     */
    #[Pure]
    public static function contains($filePath, PathFilterDTO $pathDTO)
    {
        $file = $pathDTO->includeFile;
        $folder = $pathDTO->includeFolder;
        $exceptFolder = $pathDTO->excludeFolder;
        $exceptFile = $pathDTO->excludeFile;

        [$fileName, $folderPath] = self::getFolderFile($filePath);

        if ($file) {
            return self::has($file, $fileName);
        }

        if ($exceptFile) {
            return ! self::has($exceptFile, $fileName);
        }

        if ($folder) {
            return self::has($folder, $folderPath);
        }

        if ($exceptFolder) {
            return ! self::has($exceptFolder, $folderPath);
        }

        return true;
    }

    /**
     * @param  \Generator<int, string>|string[]  $paths
     * @param  \Imanghafoori\LaravelMicroscope\PathFilterDTO  $pathDTO
     * @return \Generator<int, string>
     */
    public static function filter($paths, $pathDTO)
    {
        foreach ($paths as $absFilePath) {
            if (self::contains($absFilePath, $pathDTO)) {
                yield $absFilePath;
            }
        }
    }

    private static function has($needles, $haystack): bool
    {
        return Loop::any(
            explode(',', $needles),
            fn ($needle) => strpos($haystack, $needle) !== false
        );
    }
}
