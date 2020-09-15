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
        return \in_array(strtolower($type), [
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

    public static function expendReferences($classes, $imports)
    {
        // Here we implode the tokens to form the full namespaced class path
        $results = [];
        $namespace = '';
        foreach ($classes as $i => $importeds) {
            if ($importeds[0][0] == T_NAMESPACE) {
                unset($importeds[0]);
                foreach ($importeds as $row) {
                    $namespace .= $row[1];
                }
                continue;
            }

            $results[$i]['class'] = '';

            // attach the current namespace if it does not begin with '\'
            if ($importeds[0][1] != '\\') {
                $results[$i]['class'] = $namespace ? $namespace.'\\' : '';
            }

            foreach ($importeds as $row) {
                if (self::isBuiltinType($row[1])) {
                    unset($results[$i]);
                    continue;
                }
                if ($importeds[0][1] != '\\') {
                    if (isset(array_values($imports)[0][$importeds[0][1]][0])) {
                        $results[$i]['class'] = array_values($imports)[0][$importeds[0][1]][0];

                        for ($j = 1; $j < count($importeds); $j++) {
                            $results[$i]['class'] .= $importeds[$j][1];
                        }
                    } else {
                        $results[$i]['class'] .= $row[1];
                    }
                } else {
                    $results[$i]['class'] .= $row[1];
                }
                $results[$i]['line'] = $row[2];
                $results[$i]['namespace'] = $namespace;
            }
        }

        return $results;
    }
}
