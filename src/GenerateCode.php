<?php

namespace Imanghafoori\LaravelMicroscope;

use Illuminate\Support\Str;
use ImanGhafoori\ComposerJson\NamespaceCalculator;
use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\Stubs\ServiceProviderStub;
use Imanghafoori\TokenAnalyzer\Refactor;
use Imanghafoori\TokenAnalyzer\TokenManager;
use Symfony\Component\Finder\SplFileInfo;

class GenerateCode
{
    /**
     * Get all of the listeners and their corresponding events.
     *
     * @param  $paths
     * @param  $composerPath
     * @param  $composerNamespace
     * @param  $command
     * @return void
     */
    public static function serviceProvider($paths, $composerPath, $composerNamespace, $command)
    {
        foreach ($paths as $classFilePath) {
            /**
             * @var $classFilePath SplFileInfo
             */
            if (! Str::endsWith($classFilePath->getFilename(), ['ServiceProvider.php'])) {
                continue;
            }
            $absFilePath = $classFilePath->getRealPath();
            $content = file_get_contents($absFilePath);

            if (strlen(\trim($content)) > 10) {
                // file is not empty
                continue;
            }

            $relativePath = FilePath::getRelativePath($absFilePath);
            $correctNamespace = NamespaceCalculator::calculateCorrectNamespace($relativePath, $composerPath, $composerNamespace);

            $className = \str_replace('.php', '', $classFilePath->getFilename());
            $answer = self::ask($command, $correctNamespace.'\\'.$className);
            if (! $answer) {
                continue;
            }
            $prefix = strtolower(str_replace('ServiceProvider', '', $className));
            file_put_contents($absFilePath, ServiceProviderStub::providerContent($correctNamespace, $className, $prefix));

            self::generateFolderStructure($classFilePath, $correctNamespace, $prefix);
            self::addToProvidersArray($correctNamespace.'\\'.$className);
        }
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @return string
     */
    protected static function makeDirectory($path): string
    {
        if (! is_dir($path)) {
            @mkdir($path, 0777, true);
        }

        return $path;
    }

    private static function ask($command, $name)
    {
        return $command->getOutput()->confirm('Do you want to generate a service provider: '.$name, true);
    }

    private static function isProvidersKey($tokens, $i): bool
    {
        $token = $tokens[$i];

        return $token[0] == T_CONSTANT_ENCAPSED_STRING &&
            \trim($token[1], '\'\"') === 'providers' &&
            \in_array(T_DOUBLE_ARROW, [$tokens[$i + 1][0], $tokens[$i + 2][0]], true);
    }

    private static function addToProvidersArray($providerPath): array
    {
        $tokens = token_get_all(file_get_contents(config_path('app.php')));

        foreach ($tokens as $i => $token) {
            if (! self::isProvidersKey($tokens, $i)) {
                continue;
            }
            $closeBracketIndex = TokenManager::readBody($tokens, $i + 15, ']')[1];

            $j = $closeBracketIndex;
            while ($tokens[--$j][0] === T_WHITESPACE && $tokens[--$j][0] === T_COMMENT) {
            }

            // put a comma at the end of the array if it is not there
            $tokens[$j] !== ',' && array_splice($tokens, $j + 1, 0, [[',']]);

            array_splice($tokens, (int) $closeBracketIndex, 0, [["\n        ".$providerPath.'::class,'."\n    "]]);
            file_put_contents(config_path('app.php'), Refactor::toString($tokens));
        }

        return $tokens;
    }

    protected static function generateFolderStructure($classFilePath, $namespace, $prefix)
    {
        $_basePath = $classFilePath->getPath().DIRECTORY_SEPARATOR;
        file_put_contents($_basePath.$prefix.'_routes.php', self::routeContent($namespace));
        self::makeDirectory($_basePath.'Database'.DIRECTORY_SEPARATOR.'migrations');
        self::makeDirectory($_basePath.'views');
        self::makeDirectory($_basePath.'Http');
        self::makeDirectory($_basePath.'Database'.DIRECTORY_SEPARATOR.'Models');
    }

    protected static function routeContent($namespace): string
    {
        return "<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web'], 'namespace' => '$namespace\Http'], function () {

});";
    }
}
