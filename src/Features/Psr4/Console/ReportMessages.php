<?php

namespace Imanghafoori\LaravelMicroscope\Features\Psr4\Console;

use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;

class ReportMessages
{
    public static function reportResult($autoload, $duration, TypeStatistics $typesStats)
    {
        $messages = [];

        $max = self::getMaxNamespaceLength($autoload);

        foreach ($autoload as $composerPath => $psr4) {
            if (count($autoload) > 1) {
                $messages[] = self::getComposerFileAddress($composerPath);
            }
            $messages[] = self::getNamespaces($psr4, $typesStats, $max);
        }
        $messages[] = ErrorPrinter::lineSeparator();
        $messages[] = self::getHeaderLine($typesStats);
        $messages[] = '';

        $messages[] = self::getFinishMsg($duration);

        return $messages;
    }

    public static function getTotalChecked($count)
    {
        return " - $count namespaces were checked.";
    }

    public static function getErrorsCount($errorCount)
    {
        if ($errorCount === 1) {
            return [[PHP_EOL.'one error was found.', 'warn']];
        } elseif ($errorCount > 1) {
            return [[PHP_EOL.$errorCount.' errors were found.', 'warn']];
        } else {
            return self::noErrorFound();
        }
    }

    private static function getComposerFileAddress($composerPath): string
    {
        $composerPath = trim($composerPath, ['.', '/']);

        if ($composerPath === '') {
            $line = ' ./composer.json ';
        } else {
            $line = " ./$composerPath/composer.json ";
        }

        return self::colorizer($line, 'blue');
    }

    private static function getHeaderLine(TypeStatistics $typesStats): string
    {
        $header = self::header($typesStats->getTotalCount());
        $types = self::presentTypes($typesStats);

        return $header.'  '.PHP_EOL.$types;
    }

    private static function getFinishMsg($time)
    {
        return 'Finished In: '.self::colorizer($time.'(s)', 'blue');
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
        $results = $typesStats->iterate(
            fn ($type, $count) => " | $count ".self::colorizer($type, 'blue')
        );

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

    private static function noErrorFound()
    {
        return [
            [PHP_EOL.self::colorizer('All namespaces are correct!', 'green').self::colorizer(' You rock  \(^_^)/ ', 'blue'), 'line'],
            ['', 'line'],
        ];
    }
}
