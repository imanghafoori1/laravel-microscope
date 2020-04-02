<?php

namespace Imanghafoori\LaravelSelfTest;

use ReflectionClass;

class ParseUseStatement
{
    /** @var array */
    private static $cache = [];

    /**
     * Expands class name into full name.
     *
     * @param  string
     *
     * @param  ReflectionClass  $rc
     *
     * @return string  full name
     */
    public static function expandClassName($name, ReflectionClass $rc)
    {
        $lower = strtolower($name);
        if (empty($name)) {
            throw new \InvalidArgumentException('Class name must not be empty.');
        } elseif (self::isBuiltinType($lower)) {
            return $lower;
        } elseif ($lower === 'self' || $lower === 'static' || $lower === '$this') {
            return $rc->getName();
        } elseif ($name[0] === '\\') { // fully qualified name
            return ltrim($name, '\\');
        }
        $uses = self::getUseStatements($rc);
        $parts = explode('\\', $name, 2);
        if (isset($uses[$parts[0]])) {
            $parts[0] = $uses[$parts[0]];

            return implode('\\', $parts);
        } elseif ($rc->inNamespace()) {
            return $rc->getNamespaceName().'\\'.$name;
        } else {
            return $name;
        }
    }

    /**
     * @param  \ReflectionClass  $class
     *
     * @return array of [alias => class]
     */
    public static function getUseStatements(ReflectionClass $class)
    {
        if (! isset(self::$cache[$name = $class->getName()])) {
            if ($class->isInternal()) {
                self::$cache[$name] = [];
            } else {
                $code = file_get_contents($class->getFileName());
                self::$cache = self::parseUseStatements(token_get_all($code), $name) + self::$cache;
            }
        }

        return self::$cache[$name];
    }

    /**
     * @param  string  $type
     *
     * @return bool
     */
    public static function isBuiltinType($type)
    {
        return in_array(strtolower($type), [
            'string',
            'int',
            'float',
            'bool',
            'array',
            'callable',
        ], true);
    }

    public static function findClassReferences(&$tokens)
    {
        $c = 0;
        $collect = false;
        $classes = [];
        $force_close = false;
        $lastToken = '_';
        $imports = self::parseUseStatements($tokens);
        while ($token = current($tokens)) {
            next($tokens);
            $t = is_array($token) ? $token[0] : $token;

            if ($t == T_USE) {
                $force_close = true;
                $collect = false;
            } elseif ($t == T_NAMESPACE) {
                $force_close = false;
                $collect = true;
            } elseif ($t == T_WHITESPACE) {
                $lastToken = $token;
                continue;
            } elseif ($t == ';') {
                $force_close = false;
                if ($collect) {
                    $c++;
                }
                $collect = false;
                $lastToken = $token;
                continue;
            } elseif ( $t == '(' || $t == ')') {
                $collect = false;
                $c++;
                $lastToken = $token;
                continue;
            } elseif ( $t == T_DOUBLE_COLON ) {
                $nextToken = current($tokens);
                if (($nextToken[0] ?? '') === T_CLASS && ! $collect) {
                    $classes[$c][] = $lastToken;
                }
                $collect = false;
                $c++;
                $lastToken = $token;
                continue;
            } elseif ($t == T_NS_SEPARATOR) {
                if (! $force_close) {
                    $collect = true;
                }

                // Add the previous token,
                // In case the namespace does not start with '\'
                // like: App\User::where(...
                if ($lastToken[0] == T_STRING && $collect && ! isset($classes[$c])) {
                    $classes[$c][] = $lastToken;
                }
            } elseif ($t == T_NEW) {
                $collect = true;
                $lastToken = $token;
                continue;
            }

            if ($collect) {
                $classes[$c][] = $token;
            }
            $lastToken = $token;
        }

        // Here we implode the tokens to form the full namespaced class path
        $results = [];
        $namespace = '';
        foreach ($classes as $i => $rows) {
            if ($rows[0][0] == T_NAMESPACE) {
                unset($rows[0]);
                foreach ($rows as $row) {
                    $namespace .= $row[1];
                }
                continue;
            }

            $results[$i]['class'] = '';

            // attach the current namespace if it does not begin with '\'
            if ($rows[0][1] != '\\') {
                $results[$i]['class'] = $namespace .'\\';
            }

            foreach ($rows as $row) {
                if ($rows[0][1] != '\\') {
                    if (isset(array_values($imports)[0][$rows[0][1]][0])) {
                        $results[$i]['class'] = array_values($imports)[0][$rows[0][1]][0];
                    } else {
                        $results[$i]['class'] .= $row[1];
                    }
                } else {
                    $results[$i]['class'] .= $row[1];
                }
                $results[$i]['line'] = $row[2];
            }
        }

        return $results;
    }

    public static function findUseStatements(&$tokens)
    {
        $class = $classLevel = $level = null;
        $uses = [];
        while ($token = current($tokens)) {
            next($tokens);
            switch (is_array($token) ? $token[0] : $token) {
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

        return $uses;
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
                            return $res;
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

        return $res;
    }

    static function fetch(&$tokens, $take)
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
            if (in_array($token, (array)$take, true)) {
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
}
