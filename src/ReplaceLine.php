<?php

namespace Imanghafoori\LaravelMicroscope;

class ReplaceLine
{
    /**
     * @param  string  $file
     * @param  string  $search
     * @param  string  $replace
     */
    public static function replace($file, $search, $replace = '')
    {
        $reading = fopen($file, 'r');
        $tmpFile = fopen($file.'._tmp', 'w');

        $isReplaced = false;

        while (! feof($reading)) {
            $line = fgets($reading);

            // replace only the first occurrence in the file
            if (! $isReplaced && strstr($line, $search)) {
                $line = str_replace($search, $replace, $line);
                $isReplaced = true;
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
    }
}
