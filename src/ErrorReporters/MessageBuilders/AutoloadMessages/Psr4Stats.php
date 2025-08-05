<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\AutoloadMessages;

use Imanghafoori\LaravelMicroscope\ErrorReporters\Reporting;
use Imanghafoori\LaravelMicroscope\Features\CheckImports\Reporters\Psr4Report;
use JetBrains\PhpStorm\Pure;

class Psr4Stats
{
    use Reporting;

    /**
     * @param  array<string, array<string, int>>  $psr4
     * @return string
     */
    #[Pure]
    public static function getLines($psr4)
    {
        $lengths = [1];
        $lines = [];

        foreach ($psr4 as $psr4Namespace => $psr4Paths) {
            Psr4Report::$callback && (Psr4Report::$callback)();
            $lengths[] = strlen($psr4Namespace);
            $lines[0] = PHP_EOL.self::getPsr4Head();
            $lines[1] = self::getPsr4(max($lengths), $psr4Namespace);

            yield implode('', $lines);
            $folders = self::getFolders($psr4Paths);
            if ($folders === '') {
                yield "\x1b[1G\x1b[2K\x1b[1A";
            } else {
                yield $folders;
            }
        }
    }

    #[Pure]
    private static function getPsr4(int $maxLen, string $namespace)
    {
        return self::paddedNamespace($maxLen + 1, $namespace.':').' </>';
    }

    #[Pure]
    private static function paddedNamespace($longest, $namespace)
    {
        $padLength = $longest - strlen($namespace);

        return $namespace.str_repeat(' ', $padLength);
    }

    #[Pure]
    private static function getPsr4Head()
    {
        return '    '.self::hyphen().'<fg=red>';
    }

    /**
     * @param  \Generator  $psr4Paths
     * @return string
     */
    #[Pure]
    private static function getFolders($psr4Paths): string
    {
        $result = [];
        $i = 0;
        // consumes the generator:
        foreach ($psr4Paths as $path => $countClasses) {
            // skip if no file was found
            if (! $countClasses) {
                continue;
            }
            $i++;
            $result[$i] = [];
            $result[$i][0] = str_repeat(' ', 6);
            $result[$i][1] = self::green('./'.$path);
            $result[$i][2] = self::files($countClasses);
            if ($i > 1) {
                $result[$i - 1][0] = str_repeat(' ', 12).'- ';
                $result[$i][0] = str_repeat(' ', 12).'- ';
            }
        }

        return self::implode($result);
    }

    #[Pure]
    private static function implode($lines)
    {
        $output = '';
        foreach ($lines as $segments) {
            $output .= implode('', $segments);
        }

        return $output;
    }
}
