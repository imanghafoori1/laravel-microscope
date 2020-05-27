<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Str;
use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\Analyzers\Refactor;
use Imanghafoori\LaravelMicroscope\Analyzers\FunctionCall;
use Imanghafoori\LaravelMicroscope\Analyzers\NamespaceCorrector;

class GenerateCode
{
    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  iterable  $paths
     * @param  $composerPath
     * @param  $composerNamespace
     * @param  $command
     *
     * @return void
     */
    public static function serviceProvider($paths, $composerPath, $composerNamespace, $command)
    {

        foreach ($paths as $classFilePath) {
            /**
             * @var $classFilePath \Symfony\Component\Finder\SplFileInfo
             */
            if (! Str::endsWith($classFilePath->getFilename(), ['ServiceProvider.php'])) {
                continue;
            }
            $absFilePath = $classFilePath->getRealPath();
            $content = file_get_contents($absFilePath);

            if (strlen(trim($content)) > 10) {
                continue;
            }

            $relativePath = FilePath::getRelativePath($absFilePath);
            $correctNamespace = NamespaceCorrector::calculateCorrectNamespace($relativePath, $composerPath, $composerNamespace);

            $className = str_replace('.php', '',$classFilePath->getFilename());
            $answer = self::ask($command, $correctNamespace.'\\'.$className);
            if (! $answer) {
                continue;
            }
            file_put_contents($absFilePath, self::providerContent($correctNamespace, $className));
            $tokens = token_get_all(file_get_contents(config_path('app.php')));
            foreach($tokens as $i => $token) {
                if (! self::isProvidersKey($tokens, $i)) {
                    continue;
                }
                $closeBracketIndex = FunctionCall::readBody($tokens, $i + 15, ']')[1];

                $j = $closeBracketIndex;
                while ($tokens[--$j][0] == T_WHITESPACE && $tokens[--$j][0] == T_COMMENT) {
                }

                $tokens[$j] !== ',' && array_splice($tokens, $j + 1, 0, [[',']]);

                array_splice($tokens, (int)$closeBracketIndex, 0, [["\n        ".$correctNamespace.'\\'.$className.'::class,'."\n    "]]);
                file_put_contents(config_path('app.php'), Refactor::toString($tokens));
            }
        }
    }

    private static function ask($command, $name)
    {
        return $command->getOutput()->confirm('Do you want to generate a service provider: '.$name, true);
    }

    private static function providerContent($correctNamespace, $className)
    {
        return '<?php
                
namespace '.$correctNamespace.';

use Illuminate\Support\ServiceProvider;

class '.$className.' extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.\'/routes.php\');
    }
}
';
    }

    private static function isProvidersKey($tokens, $i)
    {
        $token = $tokens[$i];

        return $token[0] == T_CONSTANT_ENCAPSED_STRING &&
            trim($token[1], '\'\"') == 'providers' &&
            in_array(T_DOUBLE_ARROW, [$tokens[$i + 1][0], $tokens[$i + 2][0]]);
    }
}
