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
        $writing = fopen($file.'._tmp', 'w');

        $replaced = false;

        while (!feof($reading)) {
            $line = fgets($reading);
            if (stristr($line, $search)) {
                $line = $replace;
                $replaced = true;
            }
            fputs($writing, $line);
        }
        fclose($reading);
        fclose($writing);
        // might as well not overwrite the file if we didn't replace anything
        if ($replaced) {
            rename($file.'._tmp', $file);
        } else {
            unlink($file.'._tmp');
        }
    }
}

