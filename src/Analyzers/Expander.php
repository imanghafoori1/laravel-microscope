<?php

namespace Imanghafoori\LaravelMicroscope\Analyzers;

use Illuminate\Support\Str;

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
            'self',
            'static',
            'parent',
        ], true);
    }

    public static function expendReferences($classes, $imports)
    {
        // here we implode the tokens to form the full namespaced class path
        $results = [];
        $namespace = '';
        $c = 0;
        foreach ($classes as $importeds) {
            if ($importeds[0][0] == T_NAMESPACE) {
                unset($importeds[0]);
                foreach ($importeds as $row) {
                    $namespace .= $row[1];
                }
                continue;
            }

            $results[$c]['class'] = '';

            // attach the current namespace if it does not begin with '\'
            if ($importeds[0][1][0] != '\\') {
                $results[$c]['class'] = $namespace ? $namespace.'\\' : '';
            }

            foreach ($importeds as $row) {
                if (self::isBuiltinType($row[1])) {
                    unset($results[$c]);
                    $c--;
                    continue;
                }

                // if starts with "\" or is not imported by the "use"
                if ($importeds[0][1][0] != '\\' && Str::contains($importeds[0][1], '\\')) {
                    // for php 8.x
                    $tmp = explode('\\', $importeds[0][1]);
                } else {
                    $tmp = [$importeds[0][1]];
                }

                if ($importeds[0][1] == '\\' || ! isset(array_values($imports)[0][$tmp[0]][0])) {
                    $results[$c]['class'] .= $row[1];
                    $results[$c]['line'] = $row[2];
                    continue;
                }

                // reads the import from the top
                $results[$c]['class'] = array_values($imports)[0][$tmp[0]][0];

                // for half imported references
                if ($importeds[0][1][0] != '\\' && Str::contains($importeds[0][1], '\\')) {
                    // for php 8.x
                    $tmp = explode('\\', $importeds[0][1]);
                    for ($j = 1; $j < count($tmp); $j++) {
                        $results[$c]['class'] .= '\\'.$tmp[$j];
                    }
                } else {
                    // for php 7.x
                    for ($j = 1; $j < count($importeds); $j++) {
                        $results[$c]['class'] .= $importeds[$j][1];
                    }
                }

                $results[$c]['line'] = $row[2];
            }
            $c++;
        }

        return [$results, $namespace];
    }
}
