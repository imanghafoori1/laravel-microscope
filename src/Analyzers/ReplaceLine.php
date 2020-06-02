<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Imanghafoori\LaravelMicroscope\Psr4Classes;

class ReplaceLine
{
    /**
     * @param  string  $file
     * @param  string  $search
     * @param  string  $replace
     *
     * @return bool|int
     */
    public static function replaceFirst($file, $search, $replace = '', $_line = null)
    {
        $reading = fopen($file, 'r');
        $tmpFile = fopen($file.'._tmp', 'w');

        $isReplaced = false;

        $lineNum = 0;
        while (! feof($reading)) {
            $lineNum++;
            $line = fgets($reading);

            // replace only the first occurrence in the file
            if (! $isReplaced && strstr($line, $search)) {
                if (! $_line || $lineNum == $_line) {
                    $line = str_replace($search, $replace, $line);
                    $isReplaced = $lineNum;
                }
            }

            // copy the entire file to the end
            fwrite($tmpFile, $line);
        }
        fclose($reading);
        fclose($tmpFile);
        // might as well not overwrite the file if we didn't replace anything
        if ($isReplaced) {
            rename($file.'._tmp', $file);
        } else {
            unlink($file.'._tmp');
        }

        return $isReplaced;
    }

    public static function fixReference($absPath, $class, $lineNum)
    {
        $class_list = Psr4Classes::classList();
        $cls = explode('\\', $class);
        $className = array_pop($cls);
        $correct = $class_list[$className] ?? [];
        if (count($correct) === 1) {
            return self::replaceFirst($absPath, $class, $correct[0], $lineNum);
        } else {
            return false;
        }
    }
}
