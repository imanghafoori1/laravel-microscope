<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

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
        while ($i < $total) {
            $index = FunctionCall::isGlobalCall('extractBlade', $tokens, $i);

            if (! $index) {
                $i++;
                continue;
            }

            $params = FunctionCall::readParameters($tokens, $i);

            $calls[] = ($params[0][0]) ?? ($tokens[$i - 1]);
            $i++;
        }
        if (! $calls) {
            return ;
        }
        $file = file($absPath);
        $replacement = ["\n".'        @include('.$calls[0][1].')'. "\n"];
        $extracted = array_splice($file, $calls[0][2] - 1, ($calls[1][2] - $calls[0][2]) + 1, $replacement);
        $partialPath = self::find(trim($calls[0][1], '\'\"'));

        array_shift($extracted);
        array_pop($extracted);
        $partialPath = str_replace(['/','\\'], '/', $partialPath);

        self::forceFilePutContents($partialPath, implode('', $extracted));
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
