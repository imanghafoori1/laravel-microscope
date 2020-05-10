<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class ParseUseStatement
{
    private static $cache = [];

    public static function getUseStatementsByPath($namespacedClassName, $absPath = null)
    {
        if (! isset(self::$cache[$namespacedClassName])) {
            $code = file_get_contents($absPath);

            self::$cache = self::parseUseStatements(token_get_all($code), $namespacedClassName)[0] + self::$cache;
        }

        return self::$cache[$namespacedClassName];
    }

    public static function findClassReferences(&$tokens, $absFilePath)
    {
        try {
            $imports = self::parseUseStatements($tokens);
            $imports = $imports[0] ?: [$imports[1]];
            $classes = ClassReferenceFinder::process($tokens);

            return Expander::expendReferences($classes, $imports);
        } catch (\ErrorException $e) {
            self::requestIssue($absFilePath);

            return [];
        }
    }

    /**
     * Parses PHP code.
     *
     * @param $tokens
     * @param  null  $forClass
     *
     * @return array of [class => [alias => class, ...]]
     */
    public static function parseUseStatements($tokens, $forClass = null)
    {
        $namespace = $class = $classLevel = $level = null;
        $res = $uses = [];
        while ($token = current($tokens)) {
            next($tokens);
            switch (is_array($token) ? $token[0] : $token) {
                case T_NAMESPACE:
                    $namespace = ltrim(self::fetch($tokens, [
                        T_STRING,
                        T_NS_SEPARATOR,
                    ]).'\\', '\\');
                    $uses = [];
                    break;

                case T_CLASS:
                case T_INTERFACE:
                case T_TRAIT:
                    if ($name = self::fetch($tokens, T_STRING)) {
                        $class = $namespace.$name;
                        $classLevel = $level + 1;
                        $res[$class] = $uses;
                        if ($class === $forClass) {
                            return [$res, $uses];
                        }
                    }
                    break;

                case T_USE:
                    while (! $class && ($name = self::fetch($tokens, [
                        T_STRING,
                        T_NS_SEPARATOR,
                    ]))) {
                        $name = ltrim($name, '\\');
                        if (self::fetch($tokens, '{')) {
                            while ($suffix = self::fetch($tokens, [
                                T_STRING,
                                T_NS_SEPARATOR,
                            ])) {
                                if (self::fetch($tokens, T_AS)) {
                                    $uses[self::fetch($tokens, T_STRING)] = [$name.$suffix, $token[2]];
                                } else {
                                    $tmp = explode('\\', $suffix);
                                    $uses[end($tmp)] = [$name.$suffix, $token[2]];
                                }
                                if (! self::fetch($tokens, ',')) {
                                    break;
                                }
                            }
                        } elseif (self::fetch($tokens, T_AS)) {
                            $uses[self::fetch($tokens, T_STRING)] = [$name, $token[2]];
                        } else {
                            $tmp = explode('\\', $name);
                            $uses[end($tmp)] = [$name, $token[2]];
                        }
                        if (! self::fetch($tokens, ',')) {
                            break;
                        }
                    }
                    break;

                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                case '{':
                    $level++;
                    break;

                case '}':
                    if ($level === $classLevel) {
                        $class = $classLevel = null;
                    }
                    $level--;
            }
        }

        return [$res, $uses];
    }

    public static function fetch(&$tokens, $take)
    {
        $res = null;
        while ($token = current($tokens)) {
            [
                $token,
                $s,
            ] = is_array($token) ? $token : [
                $token,
                $token,
            ];
            if (in_array($token, (array) $take, true)) {
                $res .= $s;
            } elseif (! in_array($token, [
                T_DOC_COMMENT,
                T_WHITESPACE,
                T_COMMENT,
            ], true)) {
                break;
            }
            next($tokens);
        }

        return $res;
    }

    /**
     * @param $absFilePath
     */
    protected static function requestIssue($absFilePath)
    {
        dump('===========================================================');
        dump('was not able to properly parse the: '.$absFilePath.' file.');
        dump('Please open up an issue on the github repo');
        dump('https://github.com/imanghafoori1/laravel-microscope/issues');
        dump('and also send the content of the file to fix the issue.');
        dump('========================== Thanks ==========================');
        sleep(3);
    }
}
