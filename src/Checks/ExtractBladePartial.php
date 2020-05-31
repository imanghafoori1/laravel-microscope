<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;
use Imanghafoori\LaravelMicroscope\Analyzers\FunctionCall;

class ExtractBladePartial
{
    public static function check($tokens, $absPath)
    {
        // we skip the very first tokens: '<?php '
        $i = 4;
        // we skip the very end of the file.
        $total = count($tokens) - 3;
        $calls = [];
        $callsOrder = [];
        $partialName = '';
        while ($i < $total) {
            $index = FunctionCall::isGlobalCall('extractBlade', $tokens, $i);

            if (! $index) {
                $i++;
                continue;
            }

            $params = FunctionCall::readParameters($tokens, $i);

            $partialName = $params[0][0][1] ?? $partialName;
            ! in_array($partialName, $callsOrder) && $callsOrder[] = $partialName;
            $calls[$partialName][] = ($params[0][0]) ?? ($tokens[$i - 1]);

            $i++;
        }
        if (! $calls) {
            return ;
        }

        $file = file($absPath);

        $callsOrder = array_reverse($callsOrder);
        foreach($callsOrder as $paramName) {
            $call = $calls[$paramName];
            if (count($call) < 2) {
                continue;
            }
            $replacement = ['@include('.$call[0][1].')'. "\n"];

            $start = $call[0][2] - (1);
            $removedLinesNumber = ($call[1][2] - $call[0][2]) + 1;
            $extracted = array_splice($file, $start, $removedLinesNumber, $replacement);
            $partialPath = self::find(trim($call[0][1], '\'\"'));
            array_shift($extracted);
            array_pop($extracted);

            $partialPath = str_replace(['/','\\'], '/', $partialPath);

            $spaces = Str::before($extracted[0], trim($extracted[0]));
            // add space before the @include to have proper indentation.
            $file[$start] = $spaces.$file[$start];
            foreach ($extracted as $i => $line) {
                // remove spaces so that the created file
                // does not have irrelevant indentation.
                $extracted[$i] = Str::after($extracted[$i], $spaces);
            }
            self::forceFilePutContents($partialPath, implode('', $extracted));
        }

        self::forceFilePutContents($absPath, implode('', $file));

        return $tokens;
    }

    public static function find($name)
    {
        if (self::hasHintInformation($name = trim($name))) {
            return self::findNamespacedView($name);
        }

        return self::findInPaths($name, View::getFinder()->getPaths());
    }

    protected static function getPossibleViewFiles($name)
    {
        return array_map(function ($extension) use ($name) {
            return str_replace('.', DIRECTORY_SEPARATOR, $name).'.'.$extension;
        }, ['blade.php']);
    }

    protected static function findNamespacedView($name)
    {
        [$namespace, $view] = self::parseNamespaceSegments($name);

        $hints = View::getFinder()->getHints();

        return self::findInPaths($view, $hints[$namespace]);
    }

    protected static function parseNamespaceSegments($name)
    {
        $segments = explode('::', $name);

        if (count($segments) !== 2) {
            throw new InvalidArgumentException("View [{$name}] has an invalid name.");
        }

        $hints = View::getFinder()->getHints();
        if (! isset($hints[$segments[0]])) {
            throw new InvalidArgumentException("No hint path defined for [{$segments[0]}].");
        }

        return $segments;
    }

    protected static function findInPaths($name, $paths)
    {
        foreach ((array) $paths as $path) {
            foreach (self::getPossibleViewFiles($name) as $file) {
                return $viewPath = $path.DIRECTORY_SEPARATOR.$file;
            }
        }
    }

    public static function hasHintInformation($name)
    {
        return strpos($name, '::') > 0;
    }

    public static function forceFilePutContents($filepath, $message){
        try {
            $isInFolder = preg_match("/^(.*)\/([^\/]+)$/", $filepath, $filepathMatches);
            if($isInFolder) {
                $folderName = $filepathMatches[1];
//                $fileName = $filepathMatches[2];
                if (!is_dir($folderName)) {
                    mkdir($folderName, 0777, true);
                }
            }
            file_put_contents($filepath, $message);
        } catch (Exception $e) {
            echo "ERR: error writing '$message' to '$filepath', ". $e->getMessage();
        }
    }
}
