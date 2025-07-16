<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\Console;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class ReportPrinter
{

    public static function  reportResult($autoload, $time, TypeStatistics $typesStats)
    {
        $messages = [];

        $messages[] = ErrorPrinter::lineSeparator();
        $messages[] = self::getHeaderLine($typesStats);
        $messages[] = '';

        $max = self::getMaxNamespaceLength($autoload);

        foreach ($autoload as $composerPath => $psr4) {
            $messages[] = self::getComposerFileAddress($composerPath);
            $messages[] = self::getNamespaces($psr4, $typesStats, $max);
        }

        $messages[] = self::getFinishMsg($time);

        return $messages;
    }

    private static function getComposerFileAddress($composerPath): string
    {
        return ' <fg=blue>./'.trim($composerPath.'/', '/').'composer.json </>';
    }

    private static function getHeaderLine(TypeStatistics $typesStats): string
    {
        $header = self::header($typesStats->getTotalCount());
        $types = self::presentTypes($typesStats);

        return $header.'  '.PHP_EOL.$types;
    }

    private static function getFinishMsg($time): string
    {
        return 'Finished In: <fg=blue>'.$time.'(s)</>';
    }

    private static function getMaxNamespaceLength($autoload): int
    {
        $max = 0;

        foreach ($autoload as $psr4) {
            foreach ($psr4 as $namespace => $path) {
                $max = max($max, strlen($namespace));
            }
        }

        return $max;
    }
    private static function getNamespaces($psr4, TypeStatistics $typesStats, int $max): string
    {
        $output = '';

        foreach ($psr4 as $namespace => $path) {
            $count = $typesStats->namespaceCount[$namespace] ?? 0;
            $path = implode(', ', (array) $path);
            $output .= self::detailLine($count, $namespace, $max, $path);
        }

        return $output;
    }

    private static function presentTypes(TypeStatistics $typesStats)
    {
        $results = $typesStats->iterate(function ($type, $count) {
            return " | $count <fg=blue>$type</>";
        });

        return implode('', $results).' |';
    }

    private static function header($stats): string
    {
        return "<options=bold;fg=yellow> $stats entities are checked in:</>";
    }

    private static function detailLine(int $count, string $namespace, int $max, string $path): string
    {
        $spacing = str_repeat(' ', $max - strlen($namespace));
        $paddedCount = str_pad($count, 4);

        $path = self::colorizer("./$path", 'green');
        // Since the namespace ends with a back-slash
        // we have to include a space char so that
        // the '</>' does not get scaped out.
        $namespace = self::colorizer($namespace.' ', 'red');

        return "  $paddedCount - $namespace $spacing ($path)\n";
    }

    private static function colorizer($str, $color)
    {
        return '<fg='.$color.'>'.$str.'</>';
    }
}