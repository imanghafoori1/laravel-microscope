<?php

namespace Imanghafoori\LaravelMicroscope\Foundations;

class Loop
{
    /**
     * Iterates over an iterable and calls a callback for each element.
     *
     * This is a pure side-effect function that executes a callback for each
     * item in the iterable. It does not return any value and is useful for
     * performing operations that have side effects (like logging, updating
     * external state, or triggering events).
     *
     * @template TKey of array-key
     * @template TValue
     *
     * @param  \iterable<TKey, TValue>  $iterable  The collection to iterate over
     * @param  callable(TValue, TKey): void  $callback  Function to execute for each element.
     *                                                  Receives the value and key of each element.
     *                                                  Should not return meaningful values.
     * @return void
     *
     * @example Updating external state
     * $total = 0;
     * over([10, 20, 30], function($value) use (&$total) {
     *     $total += $value;
     * });
     * // $total is now 60
     */
    public static function over($iterable, callable $callback)
    {
        foreach ($iterable as $key => $value) {
            $callback($value, $key);
        }
    }

    /**
     * Iterates over nested iterables with access to both levels of keys.
     *
     * @template TOuterKey of array-key
     * @template TInnerKey of array-key
     * @template TValue
     *
     * @param  iterable<TOuterKey, iterable<TInnerKey, TValue>>  $iterable
     * @param  callable(TValue, TInnerKey, TOuterKey): mixed  $callback
     * @return void
     *
     * @throws \TypeError If any inner value is not iterable
     * @throws \Exception If inner iteration fails
     */
    public static function deepOver($iterable, callable $callback)
    {
        foreach ($iterable as $key1 => $value1) {
            foreach ($value1 as $key2 => $value2) {
                $callback($value2, $key2, $key1);
            }
        }
    }

    /**
     * @param  $iterable
     * @param  callable  $callback
     * @return positive-int
     */
    public static function walkCount($iterable, callable $callback)
    {
        $count = 0;
        foreach ($iterable as $key => $value) {
            $callback($value, $key) && $count++;
        }

        return $count;
    }

    /**
     * @param  \iterable  $iterable
     * @return positive-int
     */
    public static function countAll($iterable)
    {
        $count = 0;
        foreach ($iterable as $v) {
            $count++;
        }

        return $count;
    }

    /**
     * Transforms each item in an iterable while preserving the original keys.
     *
     * Applies a callback function to each element in the iterable, using the
     * return value as the new value for that key. The resulting array maintains
     * the same keys as the input iterable.
     *
     * This is similar to array_map() with key preservation, but works with any
     * iterable type and provides the current key to the callback function.
     *
     * @template TKey of array-key
     * @template TValue
     * @template TReturn
     *
     * @param  iterable<TKey, TValue>  $iterable  The input collection to transform
     * @param  callable(TValue, TKey): TReturn  $callback  Transformation function applied to each item.
     *                                                     Receives the value and its key, returns the new value.
     * @return array<TKey, TReturn> Array with same keys as input, but transformed values
     *
     * @example Basic value transformation
     * $result = map([1, 2, 3], fn($n) => $n * 2);
     * // Returns: [0 => 2, 1 => 4, 2 => 6]
     * @example With associative array
     * $result = map(['a' => 1, 'b' => 2], fn($v, $k) => "$k:$v");
     * // Returns: ['a' => 'a:1', 'b' => 'b:2'] (keys preserved)
     *
     * @throws \TypeError If callback returns an unsupported type for array values
     */
    public static function map($iterable, callable $callback)
    {
        $result = [];
        foreach ($iterable as $key => $value) {
            $result[$key] = $callback($value, $key);
        }

        return $result;
    }

    /**
     * Transforms iterable items into a sequentially indexed list.
     *
     * @template TKey of array-key
     * @template TValue
     * @template TReturn
     *
     * @param  iterable<TKey, TValue>  $iterable
     * @param  callable(TValue, TKey): TReturn  $callback
     * @return array<int, TReturn>
     */
    public static function mapToList($iterable, callable $callback)
    {
        $result = [];
        foreach ($iterable as $key => $value) {
            $result[] = $callback($value, $key);
        }

        return $result;
    }

    /**
     * @return bool
     */
    public static function any($values, $condition)
    {
        foreach ($values as $value) {
            if ($condition($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Transforms each item into an array and merges all results into a single array.
     *
     * Applies a callback to each item in the iterable. The callback should return
     * an array of key-value pairs. All returned arrays are merged together,
     * with later keys overwriting earlier ones in case of collisions.
     *
     * This is useful for flattening transformations where each input item can
     * produce multiple output items, or for remapping keys entirely.
     *
     * @template TKey of array-key
     * @template TValue
     * @template TMapKey of array-key
     * @template TMapValue
     *
     * @param  iterable<TKey, TValue>  $iterable  The input collection to transform
     * @param  callable(TValue, TKey): array<TMapKey, TMapValue>  $callback  Transformation function
     *                                                                       that returns an array of key-value pairs to merge
     * @return array<TMapKey, TMapValue> Merged array from all transformed items
     *
     * @example Basic key transformation
     * $result = mapKey(
     *     ['a' => 1, 'b' => 2],
     *     fn($v, $k) => ["{$k}_squared" => $v * $v]
     * );
     * // Returns: ['a_squared' => 1, 'b_squared' => 4]
     * @example One-to-many mapping
     * $result = mapKey(
     *     [1, 2],
     *     fn($v) => ["num_{$v}" => $v, "double_{$v}" => $v * 2]
     * );
     * // Returns: ['num_1' => 1, 'double_1' => 2, 'num_2' => 2, 'double_2' => 4]
     * @example Key collisions (later overwrites earlier)
     * $result = mapKey(
     *     ['a' => 1, 'b' => 2],
     *     fn($v, $k) => ['same_key' => $v]
     * );
     * // Returns: ['same_key' => 2] (value from 'b' overwrites value from 'a')
     */
    public static function mapKey($iterable, callable $callback): array
    {
        $result = [];
        foreach ($iterable as $key => $value) {
            foreach ($callback($value, $key) as $key2 => $value2) {
                $result[$key2] = $value2;
            }
        }

        return $result;
    }

    /**
     * Conditionally transforms items and merges their results into a single array.
     *
     * For each item in the iterable, if the condition callback returns true,
     * the transformation callback is called. The callback should return an array,
     * which is then merged into the result array. Keys from returned arrays
     * overwrite previous keys if they collide (standard array merge behavior).
     *
     * @template TKey of array-key
     * @template TValue
     * @template TMapKey of array-key
     * @template TMapValue
     *
     * @param  iterable<TKey, TValue>  $iterable  Input collection (array, Traversable, etc.)
     * @param  callable(TValue, TKey): bool  $if  Condition predicate
     * @param  callable(TValue, TKey): array<TMapKey, TMapValue>  $callback  Transformation function
     * @return array<TMapKey, TMapValue> Combined array from all transformed items
     *
     * @example
     * $result = mapIf(
     *     ['a' => 1, 'b' => 2, 'c' => 3],
     *     fn($v) => $v > 1,
     *     fn($v, $k) => [$k . '_doubled' => $v * 2]
     * );
     * // Returns: ['b_doubled' => 4, 'c_doubled' => 6]
     */
    public static function mapIf($iterable, callable $if, callable $callback): array
    {
        $result = [];
        foreach ($iterable as $key => $value) {
            if ($if($value, $key)) {
                foreach ($callback($value, $key) as $key2 => $value2) {
                    $result[$key2] = $value2;
                }
            }
        }

        return $result;
    }

    /**
     * Filters items from an iterable based on a callback condition.
     *
     * @template TKey of array-key
     * @template TValue
     *
     * @param  iterable<TKey, TValue>  $iterable  The iterable to filter
     * @param  callable(TValue, TKey): bool  $callback  The filter callback that returns true to keep item
     * @return array<TKey, TValue> Filtered results preserving original keys
     */
    public static function filter($iterable, $callback): array
    {
        $items = [];
        foreach ($iterable as $key => $item) {
            if ($callback($item, $key)) {
                $items[$key] = $item;
            }
        }

        return $items;
    }
}
