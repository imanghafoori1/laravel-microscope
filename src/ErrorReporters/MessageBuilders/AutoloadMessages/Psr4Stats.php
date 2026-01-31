<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters\MessageBuilders\AutoloadMessages;

use Imanghafoori\LaravelMicroscope\ErrorReporters\Reporting;
use Imanghafoori\LaravelMicroscope\Foundations\Color;
use Imanghafoori\LaravelMicroscope\Foundations\Reports\ComposerJsonReport;
use JetBrains\PhpStorm\Pure;

class Psr4Stats
{
    use Reporting;

    /**
     * @param  \Imanghafoori\LaravelMicroscope\Foundations\Iterators\DTO\Psr4StatsDTO  $psr4Stats
     * @param  int  $max
     * @return \Generator<int, string>
     */
    #[Pure]
    public static function getLines($psr4Stats, $max = 1)
    {
        $lines = [];

        foreach ($psr4Stats->stats as $psr4Namespace => $psr4Paths) {
            ComposerJsonReport::$callback && (ComposerJsonReport::$callback)();
            $lines[0] = PHP_EOL.self::getPsr4Head();
            $lines[1] = Color::red(self::getPsr4($max, $psr4Namespace));

            yield implode('', $lines);

            // consumes the generator:
            foreach ($psr4Paths as $path => $countClasses) {
                $spaces = str_repeat(' ', 6);
                $path = Color::green('./'.$path);

                yield $spaces.$path;

                $count = $countClasses();

                if ($count > 0) {
                    yield self::files($count);
                } else {
                    yield "\x1b[1G\x1b[2K\x1b[1A";
                }
            }
        }
    }

    #[Pure]
    private static function getPsr4(int $maxLen, string $namespace)
    {
        return self::paddedNamespace($maxLen + 1, $namespace.':').' ';
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
        return '    '.self::hyphen();
    }
}
