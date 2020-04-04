<?php

namespace Imanghafoori\LaravelSelfTest;

class GetClassProperties
{
    public static function fromFilePath(string $filePath)
    {
        $fp = fopen($filePath, 'r');
        $type = $class = $namespace = $buffer = '';
        $i = 0;
        while (! $class) {
            if (feof($fp)) {
                break;
            }

            $buffer .= fread($fp, 400);
            $tokens = token_get_all($buffer.'/**/');

            if (strpos($buffer, '{') === false) {
                continue;
            }

            [
                $namespace,
                $type,
                $class,
            ] = self::getImports($i, $tokens, $namespace);
        }

        return [
            ltrim($namespace, '\\'),
            $class,
            $type,
        ];
    }

    /**
     * @param  int  $i
     * @param  array  $tokens
     * @param  string  $namespace
     *
     * @return array
     */
    protected static function getImports(int $i, array $tokens, string $namespace): array
    {
        $type = $class = null;
        for (; $i < count($tokens); $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if ($tokens[$j][0] === T_STRING) {
                        $namespace .= '\\'.$tokens[$j][1];
                    } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                        break;
                    }
                }
            }

            if (in_array($tokens[$i][0], [
                T_CLASS,
                T_INTERFACE,
                T_TRAIT,
            ])) {
                $type = $tokens[$i][0];
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if (!$class && $tokens[$j] === '{') {
                        $class = $tokens[$i + 2][1];
                    }
                }
            }
        }

        return [
            $namespace,
            $type,
            $class,
        ];
    }
}
