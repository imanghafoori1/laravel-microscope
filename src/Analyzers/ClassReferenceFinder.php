<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class ClassReferenceFinder
{
    private static $lastToken = [null, null, null];

    private static $secLastToken = [null, null, null];

    private static $token = [null, null, null];

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
            '::',
            'self',
            'static',
            'parent',
        ], true);
    }

    /**
     * @param  array  $classes
     * @param  array  $imports
     *
     * @return array
     */
    public static function expendReferences($classes, $imports)
    {
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
                $results[$i]['class'] = $namespace ? $namespace.'\\' : '';
            }

            foreach ($rows as $row) {
                if (self::isBuiltinType($row[1])) {
                    unset($results[$i]);
                    continue;
                }
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

    /**
     * @param  array  $tokens
     *
     * @return array
     */
    public static function process(&$tokens)
    {
        $classes = [];
        $c = 0;
        $force_close = $implements = $collect = false;
        $isDefiningFunction = $isCatchException = $isMethodSignature = $isDefiningMethod = $isInsideMethod = $isInSideClass = false;

        while (self::$token = current($tokens)) {
            next($tokens);
            $t = self::$token[0];

            if ($t == T_USE) {
                // Since we don't want to collect use statements (imports)
                // and we want to collect the used traits on the class.
                if (! $isInSideClass) {
                    $force_close = true;
                    $collect = false;
                } else {
                    $collect = true;
                }
                self::forward();
                continue;
            } elseif ($t == T_CLASS || $t == T_TRAIT) {
                $isInSideClass = true;
            } elseif ($t == T_CATCH) {
                $collect = true;
                $isCatchException = true;
                continue;
            } elseif ($t == T_NAMESPACE) {
                $force_close = false;
                $collect = true;
            // continue;   // why we do not continue?? (0_o)
            } elseif ($t == T_FUNCTION) {
                $isDefiningFunction = true;
                if ($isInSideClass and ! $isInsideMethod) {
                    $isDefiningMethod = true;
                }
            } elseif ($t == T_VARIABLE) {
                if ($isDefiningFunction) {
                    $c++;
                }
                $collect = false;
                self::forward();
                // we do not want to collect variables
                continue;
            } elseif ($t == T_IMPLEMENTS) {
                $collect = $implements = true;
                $c++;
                continue;
            } elseif ($t == T_WHITESPACE || $t == '&') {
                // we do not want to keep track of
                // white spaces or collect them
                continue;
            } elseif ($t == ';' || $t == '}') {
                $force_close = false;
                if ($collect) {
                    $c++;
                }
                $collect = false;

                self::forward();
                continue;
            } elseif ($t == ',') {
                // for method calls: foo(new Hello, $var);
                // we do not want to collect after comma.
                $collect = ($isMethodSignature || $implements) ? true : false;
                $isInSideClass && $force_close = false;
                $c++;
                self::forward();
                continue;
            } elseif ($t == ']') {
                // for method calls: foo(new Hello, $var);
                // we do not want to collect after comma.
                $force_close = $collect = false;
                $c++;
                self::forward();
                continue;
            } elseif ($t == '{') {
                $implements = $isMethodSignature = false;
                if ($isDefiningMethod) {
                    $isDefiningMethod = false;
                    $isInsideMethod = true;
                }
                // after "extends \Some\other\Class_v"
                // we need to switch to the next level.
                if ($collect) {
                    $c++;
                    $collect = false;
                }
                continue;
            } elseif ($t == '(' || $t == ')') {
                // wrong...
                if ($t == '(' && ($isDefiningMethod || $isCatchException)) {
                    $isMethodSignature = true;
                    $collect = true;
                } else {
                    // so is calling a method by: ()
                    $collect = false;
                }
                if ($t == ')') {
                    $isDefiningFunction = false;
                    $isCatchException = false;
                }
                $c++;
                self::forward();
                continue;
            } elseif ($t == T_DOUBLE_COLON) {
                // When we reach the ::class syntax.
                // we do not want to treat: $var::method(), self::method()
                // as a real class name, so it must be of type T_STRING
                if (! $collect && ! in_array(self::$lastToken[1], ['parent', 'self', 'static']) && self::$lastToken[0] == T_STRING && (self::$secLastToken[1] ?? null) !== '->') {
                    $classes[$c][] = self::$lastToken;
                }
                $collect = false;
                $c++;
                self::forward();
                continue;
            } elseif ($t == T_NS_SEPARATOR) {
                if (! $force_close) {
                    $collect = true;
                }

                // Add the previous token,
                // In case the namespace does not start with '\'
                // like: App\User::where(...
                if (self::$lastToken[0] == T_STRING && $collect && ! isset($classes[$c])) {
                    $classes[$c][] = self::$lastToken;
                }
            } elseif ($t == T_NEW) {
                // we start to collect tokens after the new keyword.
                // unless we reach a variable name.
                $collect = true;
                self::forward();

                // we do not want to collect the new keyword itself
                continue;
            }

            if ($collect) {
                $classes[$c][] = self::$token;
            }
            self::forward();
        }

        return $classes;
    }

    protected static function forward()
    {
        self::$secLastToken = self::$lastToken;
        self::$lastToken = self::$token;
    }
}
