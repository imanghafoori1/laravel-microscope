<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\Psr4\NamespaceCorrector;
use Imanghafoori\RealtimeFacades\SmartRealTimeFacadesProvider;
use Imanghafoori\SearchReplace\Searcher;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Finder\SplFileInfo;

class FacadeDocblocks
{
    public static $command;

    public static function check($tokens, $absFilePath, SplFileInfo $classFilePath, $psr4Path, $psr4Namespace)
    {
        $facade = NamespaceCorrector::getNamespacedClassFromPath($absFilePath);

        if (! self::isFacade($facade)) {
            return null;
        }

        if (! is_string($accessor = self::getAccessor($facade))) {
            return null;
        }

        $isClass = class_exists($accessor);
        // For the interfaces, we just skip the resolution step
        // We also skip if the class is not bound on the $app
        if ((! $isClass && ! interface_exists($accessor)) || ($isClass && app()->bound($accessor))) {
            try {
                $accessor = get_class($facade::getFacadeRoot());
            } catch (\Exception $e) {
                Event::dispatch('microscope.facade.accessor_error', [$accessor, $absFilePath]);

                return;
            }
        }

        self::addDocBlocks($accessor, $facade, $tokens, $classFilePath);
    }

    private static function addDocBlocks(string $accessor, $facade, $tokens, SplFileInfo $classFilePath)
    {
        $publicMethods = (new ReflectionClass($accessor))->getMethods(ReflectionMethod::IS_PUBLIC);

        $_methods = [];
        foreach ($publicMethods as $method) {
            ! method_exists($facade, $method->getName()) && $_methods[] = $method;
        }

        if ($_methods === []) {
            return;
        }

        $docblocks = '/**'.PHP_EOL.SmartRealTimeFacadesProvider::getDocBlocks($_methods).'/';

        $s = explode('\\', $facade);
        $className = array_pop($s);
        $newVersion = self::injectDocblocks($className, $docblocks, $tokens);

        if (Filesystem::$fileSystem::file_get_contents($classFilePath) !== $newVersion) {
            Event::dispatch('microscope.facade.docblocked', [$facade, $classFilePath]);
            Filesystem::$fileSystem::file_put_contents($classFilePath, $newVersion);
        }
    }

    protected static function getAccessor($class)
    {
        $cb = (function ($class) {
            return $class::getFacadeAccessor();
        })->bindTo(null, $class);

        $accessor = $cb($class);

        return is_object($accessor) ? get_class($accessor) : $accessor;
    }

    protected static function isFacade($class)
    {
        return class_exists($class) && is_subclass_of($class, Facade::class);
    }

    private static function injectDocblocks($className, $docblocks, $tokens)
    {
        $class = "class $className extends";

        [$newVersion, $lines] = Searcher::searchReplace([
            'fix' => [
                'search' => "'<white_space>?''<doc_block>''<white_space>?'".$class,
                'replace' => "'<1>'$docblocks\n".$class,
            ],
        ], $tokens);

        if (! $lines) {
            [$newVersion] = Searcher::searchReplace([
                'fix' => [
                    'search' => "'<white_space>?'".$class,
                    'replace' => "'<1>'$docblocks\n".$class,
                ],
            ], $tokens);
        }

        return $newVersion;
    }
}
