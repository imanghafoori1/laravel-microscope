<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Support\Facades\Facade;
use Imanghafoori\FileSystem\FileSystem;
use Imanghafoori\LaravelMicroscope\Psr4\NamespaceCorrector;
use Imanghafoori\RealtimeFacades\SmartRealTimeFacadesProvider;
use Imanghafoori\SearchReplace\Searcher;
use Symfony\Component\Finder\SplFileInfo;

class FacadeDocblocks
{
    public static $command;

    public static function check($tokens, $absFilePath, SplFileInfo $classFilePath, $psr4Path, $psr4Namespace)
    {
        $class = NamespaceCorrector::getNamespacedClassFromPath($absFilePath);

        if (class_exists($class) && is_subclass_of($class, Facade::class)) {
            $cb = (function ($class) {
                return $class::getFacadeAccessor();
            })->bindTo(null, $class);

            $accessor = $cb($class);

            if (is_object($accessor)) {
                $accessor = get_class($accessor);
            }

            if (! is_string($accessor)) {
                return;
            }

            if (! class_exists($accessor) && ! interface_exists($accessor)) {
                $accessor = get_class($class::getFacadeRoot());
            }
            self::AddDocBlocks($accessor, $class, $tokens, $classFilePath);
        }
    }

    private static function AddDocBlocks(string $accessor, $class, $tokens, SplFileInfo $classFilePath)
    {
        $docblocks = '/**'.PHP_EOL.SmartRealTimeFacadesProvider::getMethodsDocblock($accessor).'/';

        $s = explode('\\', $class);
        $className = array_pop($s);
        // replacement
        [$newVersion, $lines] = Searcher::searchReplace([
            'fix' => [
                'search' => "'<doc_block>?''<white_space>?'class ".$className.' extends',
                'replace' => "'<2>'".$docblocks."\n".'class '.$className.' extends',
            ],
        ], $tokens);

        FileSystem::$fileSystem::file_put_contents($classFilePath, $newVersion);
    }
}
