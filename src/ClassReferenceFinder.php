<?php

namespace Imanghafoori\LaravelMicroscope;

class ClassReferenceFinder
{
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
        $lastToken = $secLastToken = [null, null, null];
        $isDefiningFunction = $isCatchException = $isMethodSignature = $isDefiningMethod = $isInsideMethod = $isInSideClass = false;

        while ($token = current($tokens)) {
            next($tokens);
            $t = is_array($token) ? $token[0] : $token;

            if ($t == T_USE) {
                // Since we don't want to collect use statements (imports)
                // and we want to collect the used traits on the class.
                if (! $isInSideClass) {
                    $force_close = true;
                    $collect = false;
                } else {
                    $collect = true;
                }
                $secLastToken = $lastToken;
                $lastToken = $token;
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
                $secLastToken = $lastToken;
                $lastToken = $token;
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

                $secLastToken = $lastToken;
                $lastToken = $token;
                continue;
            } elseif ($t == ',') {
                if ($isMethodSignature || $implements) {
                    $collect = true;
                } else {
                    // for method calls: foo(new Hello, $var);
                    // we do not want to collect after comma.
                    $collect = false;
                }
                $force_close = false;
                $c++;
                $secLastToken = $lastToken;
                $lastToken = $token;
                continue;
            } elseif ($t == ']') {
                // for method calls: foo(new Hello, $var);
                // we do not want to collect after comma.
                $force_close = $collect = false;
                $c++;
                $secLastToken = $lastToken;
                $lastToken = $token;
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
                $secLastToken = $lastToken;
                $lastToken = $token;
                continue;
            } elseif ($t == T_DOUBLE_COLON) {
                // When we reach the ::class syntax.
                // we do not want to treat: $var::method(), self::method()
                // as a real class name, so it must be of type T_STRING
                if (! $collect && ! in_array($lastToken[1], ['parent', 'self', 'static']) && $lastToken[0] == T_STRING && ($secLastToken[1] ?? null) !== '->') {
                    $classes[$c][] = $lastToken;
                }
                $collect = false;
                $c++;
                $secLastToken = $lastToken;
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
                // we start to collect tokens after the new keyword.
                // unless we reach a variable name.
                $collect = true;
                $secLastToken = $lastToken;
                $lastToken = $token;

                // we do not want to collect the new keyword itself
                continue;
            }

            if ($collect) {
                $classes[$c][] = $token;
            }
            $secLastToken = $lastToken;
            $lastToken = $token;
        }

        return $classes;
    }
}
