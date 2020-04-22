<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

class Expander
{
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
}
