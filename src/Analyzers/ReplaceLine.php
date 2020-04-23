<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class ReplaceLine
{
    /**
     * @param  string  $file
     * @param  string  $search
     * @param  string  $replace
     *
     * @return bool|int
     */
    public static function replaceFirst($file, $search, $replace = '')
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
                $line = str_replace($search, $replace, $line);
                $isReplaced = $lineNum;
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
}
