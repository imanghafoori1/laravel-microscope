<?php

namespace Imanghafoori\LaravelMicroscope\Checks;

use Carbon\Carbon;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\ErrorReporters\PendingError;
use Imanghafoori\LaravelMicroscope\Psr4\NamespaceCorrector;
use Imanghafoori\LaravelMicroscope\Traits\CorrectNamespace;
use Imanghafoori\TokenAnalyzer\GetClassProperties;
use Imanghafoori\TokenAnalyzer\ParseUseStatement;

class CheckIsFacadeClass
{
    use CorrectNamespace;

    public static function check($tokens, $absFilePath, $phpFilePath, $psr4Path, $psr4Namespace, $params)
    {
        try {
            self::checkApplicationClasses($tokens, $absFilePath, $phpFilePath, $psr4Path, $psr4Namespace);
        } catch (\ErrorException $e) {
            // In case a file is moved or deleted,
            // composer will need a dump autoload.
            if (! Str::endsWith($e->getFile(), 'vendor\composer\ClassLoader.php')) {
                throw $e;
            }

            self::warnDumping($e->getMessage());
            resolve(Composer::class)->dumpAutoloads();
        }
    }

    public static function warnDumping($msg)
    {
        $p = resolve(ErrorPrinter::class)->printer;
        $p->writeln('It seems composer has some trouble with autoload...');
        $p->writeln($msg);
        $p->writeln('Running "composer dump-autoload" command...  \(*_*)\  ');
    }

    private static function checkApplicationClasses($tokens, $absFilePath, $classFilePath, $psr4Path, $psr4Namespace)
    {
        // If file is empty or does not begin with <?php
        if (($tokens[0][0] ?? null) !== T_OPEN_TAG) {
            return;
        }

        [$currentNamespace, $class] = GetClassProperties::readClassDefinition($tokens);

        // It means that, there is no class/trait definition found in the file.
        if (! $class) {
            return;
        }

        event('laravel_microscope.checking_file', [$absFilePath]);

        $classFullNameSpace = self::getFullNamespace($classFilePath, $psr4Path, $psr4Namespace);

        $classReflection = new \ReflectionClass($classFullNameSpace);

        if($classReflection->isSubclassOf(Facade::class)) {
            /** @var ErrorPrinter $printer */
            $printer = app(ErrorPrinter::class);
            $printer->print($absFilePath);
        }
    }
}
