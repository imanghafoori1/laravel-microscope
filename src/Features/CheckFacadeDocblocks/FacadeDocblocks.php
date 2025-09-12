<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckFacadeDocblocks;

use Exception;
use Illuminate\Support\Facades\Facade;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\Foundations\Loop;
use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;
use Imanghafoori\RealtimeFacades\SmartRealTimeFacadesProvider;
use Imanghafoori\SearchReplace\Searcher;
use ReflectionClass;
use ReflectionMethod;

class FacadeDocblocks
{
    public static $command;

    /**
     * @var \Closure|null
     */
    public static $onError;

    /**
     * @var \Closure|null
     */
    public static $onFix;

    public static function check(PhpFileDescriptor $file)
    {
        $absFilePath = $file->getAbsolutePath();

        $fqcnFacade = $file->getNamespace();

        if (! self::isFacade($fqcnFacade)) {
            return null;
        }

        if (! is_string($accessor = self::getAccessor($fqcnFacade))) {
            return null;
        }

        $isClass = class_exists($accessor);
        // For the interfaces, we just skip the resolution step
        // We also skip if the class is not bound on the $app
        if ((! $isClass && ! interface_exists($accessor)) || ($isClass && app()->bound($accessor))) {
            try {
                $accessor = get_class($fqcnFacade::getFacadeRoot());
            } catch (Exception $e) {
                (self::$onError)($accessor, $file);

                return;
            }
        }

        self::addDocBlocks($accessor, $fqcnFacade, $file->getTokens(), $absFilePath);
    }

    private static function addDocBlocks(string $accessor, $fqcn, $tokens, $absFilePath)
    {
        $methods = Loop::filter(
            self::findPublicMethods($accessor),
            fn ($method) => self::isNeed($fqcn, $method)
        );

        if ($methods === []) {
            return;
        }

        $docblocks = '/**'.PHP_EOL.SmartRealTimeFacadesProvider::getDocBlocks($methods).'/';

        $className = basename(str_replace('\\', '/', $fqcn));
        $newVersion = self::injectDocblocks($className, $docblocks, $tokens);

        if (Filesystem::$fileSystem::file_get_contents($absFilePath) !== $newVersion) {
            (self::$onFix)($fqcn, $absFilePath);
            Filesystem::$fileSystem::file_put_contents($absFilePath, $newVersion);
        }
    }

    protected static function getAccessor($class)
    {
        $cb = (fn ($class) => $class::getFacadeAccessor())->bindTo(null, $class);

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

    private static function isNeed($fqcnFacade, ReflectionMethod $method): bool
    {
        $magicMethods = ['__invoke', '__construct', '__destruct', '__toString', '__call', '__set', '__get'];
        $methodName = $method->getName();

        return ! method_exists($fqcnFacade, $methodName) && ! $method->isStatic() && ! in_array($methodName, $magicMethods);
    }

    /**
     * @throws \ReflectionException
     */
    private static function findPublicMethods(string $accessor)
    {
        return (new ReflectionClass($accessor))->getMethods(ReflectionMethod::IS_PUBLIC);
    }
}
