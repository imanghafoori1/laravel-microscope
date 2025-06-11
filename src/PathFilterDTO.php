<?php

namespace Imanghafoori\LaravelMicroscope;

use Exception;

class PathFilterDTO
{
    public $includeFile;

    public $includeFolder;

    public $excludeFolder;

    public $excludeFile;

    public static function make($includeFile = '', $includeFolder = '', $excludeFolder = null, $excludeFile = null)
    {
        $object = new self();
        $object->includeFile = $includeFile;
        $object->includeFolder = $includeFolder;
        $object->excludeFolder = $excludeFolder;
        $object->excludeFile = $excludeFile;

        return $object;
    }

    public static function makeFromOption($command): self
    {
        try {
            $excludeFile = ltrim($command->option('except-file'), '=');
            $excludeFolder = ltrim($command->option('except-folder'), '=');
        } catch (Exception $e) {
            $excludeFile = null;
            $excludeFolder = null;
        }

        try {
            $includeFileName = ltrim($command->option('file'), '=');
            $includeFolderName = ltrim($command->option('folder'), '=');
        } catch (Exception $e) {
            $includeFileName = null;
            $includeFolderName = null;
        }

        $object = new self();
        $object->includeFile = $includeFileName;
        $object->includeFolder = $includeFolderName;
        $object->excludeFolder = $excludeFolder;
        $object->excludeFile = $excludeFile;

        return $object;
    }
}
