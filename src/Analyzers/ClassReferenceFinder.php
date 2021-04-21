<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class ClassReferenceFinder
{
    private static $lastToken = [null, null, null];

    private static $secLastToken = [null, null, null];

    private static $token = [null, null, null];

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
        $isDefiningFunction = $isCatchException = $isSignature = $isDefiningMethod = $isInsideMethod = $isInSideClass = false;

        while (self::$token = current($tokens)) {
            next($tokens);
            $t = self::$token[0];

            if ($t == T_USE) {
                // function () use ($var) {...}
                // for this type of use we do not care and continue;
                // who cares ?!
                if (self::$lastToken == ')') {
                    self::forward();
                    continue;
                }

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
                // new class {... }
                if (self::$token[1] == 'class') {
                    self::forward();
                    continue;
                }
                $isInSideClass = true;
            } elseif ($t == T_CATCH) {
                $collect = true;
                $isCatchException = true;
                continue;
            } elseif ($t == T_NAMESPACE) {
                $force_close = false;
                $collect = true;
            // continue;   // why we do not continue?? (0_o)
            } elseif (\in_array($t, [T_PUBLIC, T_PROTECTED, T_PRIVATE])) {
                $isInsideMethod = false;
            } elseif ($t == T_FUNCTION) {
                $isDefiningFunction = true;
                if ($isInSideClass and ! $isInsideMethod) {
                    $isDefiningMethod = true;
                }
            } elseif ($t == T_VARIABLE || $t == T_ELLIPSIS) {
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
                self::forward();
                continue;
            } elseif ($t == T_WHITESPACE || $t == '&') {
                // we do not want to keep track of
                // white spaces or collect them
                continue;
            } elseif (in_array($t, [';', '}', T_BOOLEAN_AND, T_BOOLEAN_OR, T_LOGICAL_OR, T_LOGICAL_AND])) {
                $force_close = false;
                if ($collect) {
                    $c++;
                }
                $collect = false;

                self::forward();
                continue;
            } elseif ($t == ',') {
                // to avoid mistaking commas in default array values with commas between args
                // example:   function hello($arg = [1, 2]) { ... }
                $collect = ($isSignature && self::$lastToken[0] == T_VARIABLE) || $implements;
                $isInSideClass && ($force_close = false);
                // for method calls: foo(new Hello, $var);
                // we do not want to collect after comma.
                $c++;
                self::forward();
                continue;
            } elseif ($t == ']') {
                $force_close = $collect = false;
                $c++;
                self::forward();
                continue;
            } elseif ($t == '{') {
                $implements = $isSignature = false;
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
                self::forward();
                continue;
            } elseif ($t == '(' || $t == ')') {
                // wrong...
                if ($t == '(' && ($isDefiningFunction || $isCatchException)) {
                    $isSignature = true;
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
            } elseif ($t == '?') {
//              for a syntax like this:
//              public function __construct(?Payment $payment) { ... }
//              we skip collecting
                self::forward();
                continue;
            } elseif ($t == T_DOUBLE_COLON) {
                // When we reach the ::class syntax.
                // we do not want to treat: $var::method(), self::method()
                // as a real class name, so it must be of type T_STRING
                if (! $collect && self::$lastToken[0] == T_STRING && ! \in_array(self::$lastToken[1], ['parent', 'self', 'static']) && (self::$secLastToken[1] ?? null) !== '->') {
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
                // currently tokenizer recognizes CONST NEW = 1; as new keyword.
                // (self::$lastToken[0] != T_CONST) && $collect = true;
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
