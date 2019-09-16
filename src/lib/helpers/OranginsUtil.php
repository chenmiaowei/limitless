<?php
/**
 * Created by PhpStorm.
 * User: air
 * Date: 2018/8/19
 * Time: 11:23 AM
 */

namespace orangins\lib\helpers;

use PhutilNumber;
use PhutilSortVector;
use PhutilJSONParser;
use Countable;
use phpDocumentor\Reflection\Types\Scalar;
use ReflectionClass;
use Yii;
use orangins\lib\OranginsObject;
use Exception;
use yii\base\InvalidArgumentException;
use yii\helpers\ArrayHelper;

/**
 * Class OranginsUtil
 * @package orangins\lib\helpers
 */
class OranginsUtil extends OranginsObject
{
    /**
     * @param $list
     * @param $method
     * @param null $key_method
     * @return array
     */
    public static function mpull($list, $method, $key_method = null)
    {
        $result = array();
        /**
         * @var  $key
         * @var OranginsObject $object
         */
        foreach ($list as $key => $object) {
            if ($key_method !== null) {
                if ($object->hasMethod($key_method)) {
                    $key = $object->$key_method();
                } else {
                    $key = ArrayHelper::getValue($object, $key_method);
                }
            }
            if ($method !== null) {
                if ($object->hasMethod($method)) {
                    $value = $object->$method();
                } else {
                    $value = ArrayHelper::getValue($object, $method);
                }
            } else {
                $value = $object;
            }
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * @param $class_name
     * @param array $argv
     * @return object
     * @throws \ReflectionException
     */
    public static function createObject($class_name, $argv = null)
    {
        $reflector = new ReflectionClass($class_name);
        if ($argv) {
            return $reflector->newInstanceArgs($argv);
        } else {
            return $reflector->newInstance();
        }
    }

    /**
     * @param $string
     * @return mixed

     * @throws \PhutilJSONParserException
     */
    public static function phutil_json_decode($string)
    {
        $result = @json_decode($string, true);

        if (!is_array($result)) {
            // Failed to decode the JSON. Try to use @{class:PhutilJSONParser} instead.
            // This will probably fail, but will throw a useful exception.
            $parser = new PhutilJSONParser();
            $result = $parser->parse($string);
        }

        return $result;
    }


    /**
     * Encode a value in JSON, raising an exception if it can not be encoded.
     *
     * @param $value
     * @return string JSON representation of the value.
     * @throws Exception
     */
    public static function phutil_json_encode($value)
    {
        $result = @json_encode($value);
        if ($result === false) {
            $reason = self::phutil_validate_json($value);
            if (function_exists('json_last_error')) {
                $err = json_last_error();
                if (function_exists('json_last_error_msg')) {
                    $msg = json_last_error_msg();
                    $extra = \Yii::t("app", '#{0}: {1}', [
                        $err, $msg
                    ]);
                } else {
                    $extra = \Yii::t("app", '#{0}', [
                        $err
                    ]);
                }
            } else {
                $extra = null;
            }

            if ($extra) {
                $message = \Yii::t("app",
                    'Failed to JSON encode value ({0}): {1}.',
                    [
                        $extra,
                        $reason
                    ]);
            } else {
                $message = \Yii::t("app",
                    'Failed to JSON encode value: {0}.',
                    [
                        $reason
                    ]);
            }

            throw new Exception($message);
        }

        return $result;
    }


    /**
     * Produce a human-readable explanation why a value can not be JSON-encoded.
     *
     * @param $value
     * @param string $path
     * @return string|null Explanation of why it can't be encoded, or null.
     */
    public static function phutil_validate_json($value, $path = '')
    {
        if ($value === null) {
            return null;
        }

        if ($value === true) {
            return null;
        }

        if ($value === false) {
            return null;
        }

        if (is_int($value)) {
            return null;
        }

        if (is_float($value)) {
            return null;
        }

        if (is_array($value)) {
            foreach ($value as $key => $subvalue) {
                if (strlen($path)) {
                    $full_key = $path . ' > ';
                } else {
                    $full_key = '';
                }

                if (!OranginsUtf8::phutil_is_utf8($key)) {
                    $full_key = $full_key . OranginsUtf8::phutil_utf8ize($key);
                    return \Yii::t("app",
                        'Dictionary key "{0}" is not valid UTF8, and cannot be JSON encoded.',
                        [
                            $full_key
                        ]);
                }

                $full_key .= $key;
                $result = self::phutil_validate_json($subvalue, $full_key);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        if (is_string($value)) {
            if (!OranginsUtf8::phutil_is_utf8($value)) {
                $display = substr($value, 0, 256);
                $display = OranginsUtf8::phutil_utf8ize($display);
                if (!strlen($path)) {
                    return \Yii::t("app",
                        'String value is not valid UTF8, and can not be JSON encoded: {0}',
                        [
                            $display
                        ]);
                } else {
                    return \Yii::t("app",
                        'Dictionary value at key "{0}" is not valid UTF8, and cannot be ' .
                        'JSON encoded: {1}',
                        [
                            $path,
                            $display
                        ]);
                }
            }
        }

        return null;
    }


    /**
     * Selects a list of keys from an array, returning a new array with only the
     * key-value pairs identified by the selected keys, in the specified order.
     *
     * Note that since this function orders keys in the result according to the
     * order they appear in the list of keys, there are effectively two common
     * uses: either reducing a large dictionary to a smaller one, or changing the
     * key order on an existing dictionary.
     *
     * @param array $dict
     * @param array $keys
     * @return array Dictionary of only those key-value pairs where the key was
     *                 present in the list of keys to select. Ordering is
     *                 determined by the list order.
     */
    public static function arraySelectKeys(array $dict, array $keys)
    {
        $result = array();
        foreach ($keys as $key) {
            if (array_key_exists($key, $dict)) {
                $result[$key] = $dict[$key];
            }
        }
        return $result;
    }


    /**
     * Similar to @{function:coalesce}, but less strict: returns the first
     * non-`empty()` argument, instead of the first argument that is strictly
     * non-`null`. If no argument is nonempty, it returns the last argument. This
     * is useful idiomatically for setting defaults:
     *
     *   $display_name = nonempty($user_name, $full_name, "Anonymous");
     *
     * @param  ...         Zero or more arguments of any type.
     * @return mixed       First non-`empty()` arg, or last arg if no such arg
     *                     exists, or null if you passed in zero args.
     */
    public static function nonempty(/* ... */)
    {
        $args = func_get_args();
        $result = null;
        foreach ($args as $arg) {
            $result = $arg;
            if ($arg) {
                break;
            }
        }
        return $result;
    }

    /**
     * Returns the last key of an array.
     *
     * @param    array       Array to retrieve the last key from.
     * @return   int|string  The last key of the array.
     */
    public static function last_key(array $arr)
    {
        end($arr);
        return key($arr);
    }


    /**
     * Merge a vector of arrays performantly. This has the same semantics as
     * array_merge(), so these calls are equivalent:
     *
     *   array_merge($a, $b, $c);
     *   array_mergev(array($a, $b, $c));
     *
     * However, when you have a vector of arrays, it is vastly more performant to
     * merge them with this function than by calling array_merge() in a loop,
     * because using a loop generates an intermediary array on each iteration.
     *
     * @param array $arrayv
     * @return array|mixed [] Arrays, merged with array_merge() semantics.
     */
    public static function array_mergev(array $arrayv)
    {
        if (!$arrayv) {
            return array();
        }

        foreach ($arrayv as $key => $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException(
                    Yii::t("app",
                        'Expected all items passed to `{0}` to be arrays, but ' .
                        'argument with key "{0}" has type "{1}".',
                        [
                            __FUNCTION__ . '()',
                            $key,
                            gettype($item)
                        ]));
            }
        }

        return call_user_func_array('array_merge', $arrayv);
    }


    /**
     * Selects a list of keys from an array, returning a new array with only the
     * key-value pairs identified by the selected keys, in the specified order.
     *
     * Note that since this function orders keys in the result according to the
     * order they appear in the list of keys, there are effectively two common
     * uses: either reducing a large dictionary to a smaller one, or changing the
     * key order on an existing dictionary.
     *
     * @param array $dict
     * @param array $keys
     * @return array
     *                 present in the list of keys to select. Ordering is
     *                 determined by the list order.
     */
    public static function array_select_keys(array $dict, array $keys)
    {
        $result = array();
        foreach ($keys as $key) {
            if (array_key_exists($key, $dict)) {
                $result[$key] = $dict[$key];
            }
        }
        return $result;
    }

    /**
     * Checks if all values of array are instances of the passed class. Throws
     * `InvalidArgumentException` if it isn't true for any value.
     *
     * @param  array
     * @param  string  Name of the class or 'array' to check arrays.
     * @return array   Returns passed array.
     */
    public static function assert_instances_of(array $arr, $class)
    {
        $is_array = !strcasecmp($class, 'array');

        foreach ($arr as $key => $object) {
            if ($is_array) {
                if (!is_array($object)) {
                    $given = gettype($object);
                    throw new InvalidArgumentException(
                        Yii::t("app",
                            "Array item with key '{0}' must be of type array, {1} given.",
                            [
                                $key,
                                $given
                            ]));
                }

            } else if (!($object instanceof $class)) {
                $given = gettype($object);
                if (is_object($object)) {
                    $given = Yii::t("app", 'instance of {0}', [
                        get_class($object)
                    ]);
                }
                throw new InvalidArgumentException(
                    Yii::t("app",
                        "Array item with key '{0}' must be an instance of {1}, {2} given.",
                        [
                            $key,
                            $class,
                            $given
                        ]));
            }
        }

        return $arr;
    }


    /**
     * Invokes the "new" operator with a vector of arguments. There is no way to
     * `call_user_func_array()` on a class constructor, so you can instead use this
     * function:
     *
     *   $obj = newv($class_name, $argv);
     *
     * That is, these two statements are equivalent:
     *
     *   $pancake = new Pancake('Blueberry', 'Maple Syrup', true);
     *   $pancake = newv('Pancake', array('Blueberry', 'Maple Syrup', true));
     *
     * DO NOT solve this problem in other, more creative ways! Three popular
     * alternatives are:
     *
     *   - Build a fake serialized object and unserialize it.
     *   - Invoke the constructor twice.
     *   - just use `eval()` lol
     *
     * These are really bad solutions to the problem because they can have side
     * effects (e.g., __wakeup()) and give you an object in an otherwise impossible
     * state. Please endeavor to keep your objects in possible states.
     *
     * If you own the classes you're doing this for, you should consider whether
     * or not restructuring your code (for instance, by creating static
     * construction methods) might make it cleaner before using `newv()`. Static
     * constructors can be invoked with `call_user_func_array()`, and may give your
     * class a cleaner and more descriptive API.
     *
     * @param  string  The name of a class.
     * @param array $argv
     * @return object
     *                 the argument vector to its constructor.
     * @throws \ReflectionException
     */
    public static function newv($class_name, array $argv)
    {
        $reflector = new ReflectionClass($class_name);
        if ($argv) {
            return $reflector->newInstanceArgs($argv);
        } else {
            return $reflector->newInstance();
        }
    }


    /**
     * Returns the first element of an array. Exactly like reset(), but doesn't
     * choke if you pass it some non-referenceable value like the return value of
     * a function.
     *
     * @param    array Array to retrieve the first element from.
     * @return mixed
     */
    public static function head(array $arr)
    {
        return reset($arr);
    }

    /**
     * Returns the last element of an array. This is exactly like `end()` except
     * that it won't warn you if you pass some non-referencable array to
     * it -- e.g., the result of some other array operation.
     *
     * @param    array Array to retrieve the last element from.
     * @return mixed
     */
    public static function last(array $arr)
    {
        return end($arr);
    }


    /**
     * Attempt to censor any plaintext credentials from a string.
     *
     * The major use case here is to censor usernames and passwords from command
     * output. For example, when `git fetch` fails, the output includes credentials
     * for authenticated HTTP remotes.
     *
     * @param   string  Some block of text.
     * @return  string  A similar block of text, but with credentials that could
     *                  be identified censored.
     */
    public static function phutil_censor_credentials($string)
    {
        return preg_replace(',(?<=://)([^/@\s]+)(?=@|$),', '********', $string);
    }

    /**
     * @return bool
     * @author 陈妙威
     */
    public static function phutil_is_windows()
    {
        // We can also use PHP_OS, but that's kind of sketchy because it returns
        // "WINNT" for Windows 7 and "Darwin" for Mac OS X. Practically, testing for
        // DIRECTORY_SEPARATOR is more straightforward.
        return (DIRECTORY_SEPARATOR != '/');
    }

    /**
     * Split a corpus of text into lines. This function splits on "\n", "\r\n", or
     * a mixture of any of them.
     *
     * NOTE: This function does not treat "\r" on its own as a newline because none
     * of SVN, Git or Mercurial do on any OS.
     *
     * @param string Block of text to be split into lines.
     * @param bool If true, retain line endings in result strings.
     * @return array
     */
    public static function phutil_split_lines($corpus, $retain_endings = true)
    {
        if (!strlen($corpus)) {
            return array('');
        }

        // Split on "\r\n" or "\n".
        if ($retain_endings) {
            $lines = preg_split('/(?<=\n)/', $corpus);
        } else {
            $lines = preg_split('/\r?\n/', $corpus);
        }

        // If the text ends with "\n" or similar, we'll end up with an empty string
        // at the end; discard it.
        if (end($lines) == '') {
            array_pop($lines);
        }

        if ($corpus instanceof PhutilSafeHTML) {
            return array_map('phutil_safe_html', $lines);
        }

        return $lines;
    }


    /**
     * Convert a human-readable unit description into a numeric one. This function
     * allows you to replace this:
     *
     *   COUNTEREXAMPLE
     *   $ttl = (60 * 60 * 24 * 30); // 30 days
     *
     * ...with this:
     *
     *   $ttl = phutil_units('30 days in seconds');
     *
     * ...which is self-documenting and difficult to make a mistake with.
     *
     * @param   string  Human readable description of a unit quantity.
     * @return  int     Quantity of specified unit.
     */
    public static function phutil_units($description)
    {
        $matches = null;
        if (!preg_match('/^(\d+) (\w+) in (\w+)$/', $description, $matches)) {
            throw new InvalidArgumentException(
                Yii::t("app",
                    'Unable to parse unit specification (expected a specification in the ' .
                    'form "{0}"): {1}',
                    [
                        '5 days in seconds',
                        $description
                    ]));
        }

        $quantity = (int)$matches[1];
        $src_unit = $matches[2];
        $dst_unit = $matches[3];

        $is_divisor = false;

        switch ($dst_unit) {
            case 'seconds':
                switch ($src_unit) {
                    case 'second':
                    case 'seconds':
                        $factor = 1;
                        break;
                    case 'minute':
                    case 'minutes':
                        $factor = 60;
                        break;
                    case 'hour':
                    case 'hours':
                        $factor = 60 * 60;
                        break;
                    case 'day':
                    case 'days':
                        $factor = 60 * 60 * 24;
                        break;
                    default:
                        throw new InvalidArgumentException(
                            Yii::t("app",
                                'This function can not convert from the unit "{1}".',
                                [
                                    $src_unit
                                ]));
                }
                break;
            case 'bytes':
                switch ($src_unit) {
                    case 'byte':
                    case 'bytes':
                        $factor = 1;
                        break;
                    case 'bit':
                    case 'bits':
                        $factor = 8;
                        $is_divisor = true;
                        break;
                    default:
                        throw new InvalidArgumentException(
                            Yii::t("app",
                                'This function can not convert from the unit "{1}".',
                                [
                                    $src_unit
                                ]));
                }
                break;
            default:
                throw new InvalidArgumentException(
                    Yii::t("app",
                        'This function can not convert into the unit "{1}".',
                        [
                            $dst_unit
                        ]));
        }

        if ($is_divisor) {
            if ($quantity % $factor) {
                throw new InvalidArgumentException(
                    Yii::t("app",
                        '"{1}" is not an exact quantity.',
                        [
                            $description
                        ]));
            }
            return (int)($quantity / $factor);
        } else {
            return $quantity * $factor;
        }
    }


    /**
     * Sort a list of objects by the return value of some method. In PHP, this is
     * often vastly more efficient than `usort()` and similar.
     *
     *    // Sort a list of Duck objects by name.
     *    $sorted = msort($ducks, 'getName');
     *
     * It is usually significantly more efficient to define an ordering method
     * on objects and call `msort()` than to write a comparator. It is often more
     * convenient, as well.
     *
     * NOTE: This method does not take the list by reference; it returns a new list.
     *
     * @param array $list List of objects to sort by some property.
     * @param $method
     * @return array
     */
    public static function msort(array $list, $method)
    {
        $surrogate = self::mpull($list, $method);

        asort($surrogate);

        $result = array();
        foreach ($surrogate as $key => $value) {
            $result[$key] = $list[$key];
        }

        return $result;
    }


    /**
     * Returns a parsable string representation of a variable.
     *
     * This function is intended to behave similarly to PHP's `var_export` function,
     * but the output is intended to follow our style conventions.
     *
     * @param  array    The variable you want to export.
     * @return string
     */
    public static function phutil_var_export($var)
    {
        // `var_export(null, true)` returns `"NULL"` (in uppercase).
        if ($var === null) {
            return 'null';
        }

        // PHP's `var_export` doesn't format arrays very nicely. In particular:
        //
        //   - An empty array is split over two lines (`"array (\n)"`).
        //   - A space separates "array" and the first opening brace.
        //   - Non-associative arrays are returned as associative arrays with an
        //     integer key.
        //
        if (is_array($var)) {
            if (count($var) === 0) {
                return 'array()';
            }

            // Don't show keys for non-associative arrays.
            $show_keys = (array_keys($var) !== range(0, count($var) - 1));

            $output = array();
            $output[] = 'array(';

            foreach ($var as $key => $value) {
                // Adjust the indentation of the value.
                $value = str_replace("\n", "\n  ", self::phutil_var_export($value));
                $output[] = '  ' .
                    ($show_keys ? var_export($key, true) . ' => ' : '') .
                    $value . ',';
            }

            $output[] = ')';
            return implode("\n", $output);
        }

        // Let PHP handle everything else.
        return var_export($var, true);
    }


    /**
     * Choose an index from a list of arrays. Short for "index pull", this function
     * works just like @{function:mpull}, except that it operates on a list of
     * arrays and selects an index from them instead of operating on a list of
     * objects and calling a method on them.
     *
     * This function simplifies a common type of mapping operation:
     *
     *    COUNTEREXAMPLE
     *    $names = array();
     *    foreach ($list as $key => $dict) {
     *      $names[$key] = $dict['name'];
     *    }
     *
     * With ipull():
     *
     *    $names = ipull($list, 'name');
     *
     * See @{function:mpull} for more usage examples.
     *
     * @param   array          Some list of arrays.
     * @param   scalar|null   Determines which **values** will appear in the result
     *                        array. Use a scalar to select that index from each
     *                        array, or null to preserve the arrays unmodified as
     *                        values.
     * @param   scalar|null   Determines which **keys** will appear in the result
     *                        array. Use a scalar to select that index from each
     *                        array, or null to preserve the array keys.
     * @return array
     *                        to whatever you passed for `$index` and `$key_index`.
     */
    public static function ipull(array $list, $index, $key_index = null)
    {
        $result = array();
        foreach ($list as $key => $array) {
            if ($key_index !== null) {
                $key = $array[$key_index];
            }
            if ($index !== null) {
                $value = $array[$index];
            } else {
                $value = $array;
            }
            $result[$key] = $value;
        }
        return $result;
    }


    /**
     * Simplifies a common use of `array_combine()`. Specifically, this:
     *
     *   COUNTEREXAMPLE:
     *   if ($list) {
     *     $result = array_combine($list, $list);
     *   } else {
     *     // Prior to PHP 5.4, array_combine() failed if given empty arrays.
     *     $result = array();
     *   }
     *
     * ...is equivalent to this:
     *
     *   $result = array_fuse($list);
     *
     * @param   array  List of scalars.
     * @return array
     */
    public static function array_fuse(array $list)
    {
        if ($list) {
            return array_combine($list, $list);
        }
        return array();
    }

    /**
     * Returns the first argument which is not strictly null, or `null` if there
     * are no such arguments. Identical to the MySQL function of the same name.
     *
     * @param  ...         Zero or more arguments of any type.
     * @return mixed       First non-`null` arg, or null if no such arg exists.
     */
    public static function coalesce(/* ... */)
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            if ($arg !== null) {
                return $arg;
            }
        }
        return null;
    }

    /**
     * Compute the number of microseconds that have elapsed since an earlier
     * timestamp (from `microtime(true)`).
     *
     * @param double Microsecond-precision timestamp, from `microtime(true)`.
     * @return int Elapsed microseconds.
     * @throws Exception
     */
    public static function phutil_microseconds_since($timestamp)
    {
        if (!is_float($timestamp)) {
            throw new Exception(
                \Yii::t("app",
                    'Argument to "phutil_microseconds_since(...)" should be a value ' .
                    'returned from "microtime(true)".'));
        }

        $delta = (microtime(true) - $timestamp);
        $delta = 1000000 * $delta;
        $delta = (int)$delta;

        return $delta;
    }


    /**
     * Group a list of objects by the result of some method, similar to how
     * GROUP BY works in an SQL query. This function simplifies grouping objects
     * by some property:
     *
     *    COUNTEREXAMPLE
     *    $animals_by_species = array();
     *    foreach ($animals as $animal) {
     *      $animals_by_species[$animal->getSpecies()][] = $animal;
     *    }
     *
     * This can be expressed more tersely with mgroup():
     *
     *    $animals_by_species = mgroup($animals, 'getSpecies');
     *
     * In either case, the result is a dictionary which maps species (e.g., like
     * "dog") to lists of animals with that property, so all the dogs are grouped
     * together and all the cats are grouped together, or whatever super
     * businessesey thing is actually happening in your problem domain.
     *
     * See also @{function:igroup}, which works the same way but operates on
     * array indexes.
     *
     * @param   array    List of objects to group by some property.
     * @param   string  Name of a method, like 'getType', to call on each object
     *                  in order to determine which group it should be placed into.
     * @param   ...     Zero or more additional method names, to subgroup the
     *                  groups.
     * @return  array    Dictionary mapping distinct method returns to lists of
     *                  all objects which returned that value.
     */
    public static function mgroup(array $list, $by /* , ... */)
    {
        $map = self::mpull($list, $by);

        $groups = array();
        foreach ($map as $group) {
            // Can't array_fill_keys() here because 'false' gets encoded wrong.
            $groups[$group] = array();
        }

        foreach ($map as $key => $group) {
            $groups[$group][$key] = $list[$key];
        }

        $args = func_get_args();
        $args = array_slice($args, 2);
        if ($args) {
            array_unshift($args, null);
            foreach ($groups as $group_key => $grouped) {
                $args[0] = $grouped;
                $groups[$group_key] = call_user_func_array([self::class, 'mgroup'], $args);
            }
        }

        return $groups;
    }


    /**
     * Sort a list of objects by a sort vector.
     *
     * This sort is stable, well-behaved, and more efficient than `usort()`.
     *
     * @param array List of objects to sort.
     * @param string Name of a method to call on each object. The method must
     *   return a @{class:PhutilSortVector}.
     * @return array Objects ordered by the vectors.
     * @throws Exception
     */
    public static function msortv(array $list, $method)
    {
        $surrogate = self::mpull($list, $method);

        $index = 0;
        foreach ($surrogate as $key => $value) {
            if (!($value instanceof PhutilSortVector)) {
                throw new Exception(
                    Yii::t("app",
                        'Objects passed to "{0}" must return sort vectors (objects of ' .
                        'class "{1}") from the specified method ("{2}"). One object (with ' .
                        'key "{3}") did not.',
                        [
                            'msortv()',
                            'PhutilSortVector',
                            $method,
                            $key
                        ]));
            }

            // Add the original index to keep the sort stable.
            $value->addInt($index++);

            $surrogate[$key] = (string)$value;
        }

        asort($surrogate, SORT_STRING);

        $result = array();
        foreach ($surrogate as $key => $value) {
            $result[$key] = $list[$key];
        }

        return $result;
    }


    /**
     * Filter a list of objects by executing a method across all the objects and
     * filter out the ones with empty() results. this function works just like
     * @{function:ifilter}, except that it operates on a list of objects instead
     * of a list of arrays.
     *
     * For example, to remove all objects with no children from a list, where
     * 'hasChildren' is a method name, do this:
     *
     *   mfilter($list, 'hasChildren');
     *
     * The optional third parameter allows you to negate the operation and filter
     * out nonempty objects. To remove all objects that DO have children, do this:
     *
     *   mfilter($list, 'hasChildren', true);
     *
     * @param  array        List of objects to filter.
     * @param  string       A method name.
     * @param  bool         Optionally, pass true to drop objects which pass the
     *                      filter instead of keeping them.
     * @return array        List of objects which pass the filter.
     */
    public static function mfilter(array $list, $method, $negate = false)
    {
        if (!is_string($method)) {
            throw new InvalidArgumentException(Yii::t("app", 'Argument method is not a string.'));
        }

        $result = array();
        foreach ($list as $key => $object) {
            $value = $object->$method();

            if (!$negate) {
                if (!empty($value)) {
                    $result[$key] = $object;
                }
            } else {
                if (empty($value)) {
                    $result[$key] = $object;
                }
            }
        }

        return $result;
    }

    /**
     * Returns the first key of an array.
     *
     * @param    array       Array to retrieve the first key from.
     * @return   int|string  The first key of the array.
     */
    public static function head_key(array $arr)
    {
        reset($arr);
        return key($arr);
    }


    /**
     * Sort a list of arrays by the value of some index. This method is identical to
     * @{function:msort}, but operates on a list of arrays instead of a list of
     * objects.
     *
     * @param   array    List of arrays to sort by some index value.
     * @param   string  Index to access on each object; the return values
     *                  will be used to sort the list.
     * @return  array    Arrays ordered by the index values.
     */
    public static function isort(array $list, $index)
    {
        $surrogate = self::ipull($list, $index);

        asort($surrogate);

        $result = array();
        foreach ($surrogate as $key => $value) {
            $result[$key] = $list[$key];
        }

        return $result;
    }


    /**
     * Group a list of arrays by the value of some index. This function is the same
     * as @{function:mgroup}, except it operates on the values of array indexes
     * rather than the return values of method calls.
     *
     * @param   array    List of arrays to group by some index value.
     * @param   string  Name of an index to select from each array in order to
     *                  determine which group it should be placed into.
     * @param   ...     Zero or more additional indexes names, to subgroup the
     *                  groups.
     * @return  array    Dictionary mapping distinct index values to lists of
     *                  all objects which had that value at the index.
     */
    public static function igroup(array $list, $by /* , ... */)
    {
        $map = self::ipull($list, $by);

        $groups = array();
        foreach ($map as $group) {
            $groups[$group] = array();
        }

        foreach ($map as $key => $group) {
            $groups[$group][$key] = $list[$key];
        }

        $args = func_get_args();
        $args = array_slice($args, 2);
        if ($args) {
            array_unshift($args, null);
            foreach ($groups as $group_key => $grouped) {
                $args[0] = $grouped;
                $groups[$group_key] = call_user_func_array('igroup', $args);
            }
        }

        return $groups;
    }


    /**
     * Assert that passed data can be converted to string.
     *
     * @param  string    Assert that this data is valid.
     * @return void
     *
     * @task   assert
     */
    public static function assert_stringlike($parameter)
    {
        switch (gettype($parameter)) {
            case 'string':
            case 'NULL':
            case 'boolean':
            case 'double':
            case 'integer':
                return;
            case 'object':
                if (method_exists($parameter, '__toString')) {
                    return;
                }
                break;
            case 'array':
            case 'resource':
            case 'unknown type':
            default:
                break;
        }

        throw new InvalidArgumentException(
            Yii::t('app',
                'Argument must be scalar or object which implements {0}!',
                [
                    '__toString()'
                ]));
    }


    /**
     * Compare two hashes for equality.
     *
     * This function defuses two attacks: timing attacks and type juggling attacks.
     *
     * In a timing attack, the attacker observes that strings which match the
     * secret take slightly longer to fail to match because more characters are
     * compared. By testing a large number of strings, they can learn the secret
     * character by character. This defuses timing attacks by always doing the
     * same amount of work.
     *
     * In a type juggling attack, an attacker takes advantage of PHP's type rules
     * where `"0" == "0e12345"` for any exponent. A portion of of hexadecimal
     * hashes match this pattern and are vulnerable. This defuses this attack by
     * performing bytewise character-by-character comparison.
     *
     * It is questionable how practical these attacks are, but they are possible
     * in theory and defusing them is straightforward.
     *
     * @param string First hash.
     * @param string Second hash.
     * @return bool True if hashes are identical.
     * @throws Exception
     */
    public static function phutil_hashes_are_identical($u, $v)
    {
        if (!is_string($u)) {
            throw new Exception(\Yii::t("app", 'First hash argument must be a string.'));
        }

        if (!is_string($v)) {
            throw new Exception(\Yii::t("app", 'Second hash argument must be a string.'));
        }

        if (strlen($u) !== strlen($v)) {
            return false;
        }

        $len = strlen($v);

        $bits = 0;
        for ($ii = 0; $ii < $len; $ii++) {
            $bits |= (ord($u[$ii]) ^ ord($v[$ii]));
        }

        return ($bits === 0);
    }

    /**
     * Count all elements in an array, or something in an object.
     *
     * @param  array|Countable  A countable object.
     * @return PhutilNumber     Returns the number of elements in the input
     *                          parameter.
     */
    public static function phutil_count($countable)
    {
        if (!(is_array($countable) || $countable instanceof Countable)) {
            throw new InvalidArgumentException(\Yii::t("app", 'Argument should be countable.'));
        }

        return new PhutilNumber(count($countable));
    }

    /**
     * @param $float1
     * @param $float2
     * @param string $operator
     * @return bool
     * @author 陈妙威
     */
    public static function compareFloatNumbers($float1, $float2, $operator = '=')
    {
        // Check numbers to 5 digits of precision
        $epsilon = 0.00001;

        $float1 = (float)$float1;
        $float2 = (float)$float2;

        switch ($operator) {
            // equal
            case "=":
            case "eq":
                {
                    if (abs($float1 - $float2) < $epsilon) {
                        return true;
                    }
                    break;
                }
            // less than
            case "<":
            case "lt":
                {
                    if (abs($float1 - $float2) < $epsilon) {
                        return false;
                    } else {
                        if ($float1 < $float2) {
                            return true;
                        }
                    }
                    break;
                }
            // less than or equal
            case "<=":
            case "lte":
                {
                    if (self::compareFloatNumbers($float1, $float2, '<') || self::compareFloatNumbers($float1, $float2, '=')) {
                        return true;
                    }
                    break;
                }
            // greater than
            case ">":
            case "gt":
                {
                    if (abs($float1 - $float2) < $epsilon) {
                        return false;
                    } else {
                        if ($float1 > $float2) {
                            return true;
                        }
                    }
                    break;
                }
            // greater than or equal
            case ">=":
            case "gte":
                {
                    if (self::compareFloatNumbers($float1, $float2, '>') || self::compareFloatNumbers($float1, $float2, '=')) {
                        return true;
                    }
                    break;
                }
            case "<>":
            case "!=":
            case "ne":
                {
                    if (abs($float1 - $float2) > $epsilon) {
                        return true;
                    }
                    break;
                }
            default:
                {
                    die("Unknown operator '" . $operator . "' in compareFloatNumbers()");
                }
        }

        return false;
    }

    /**
     * @param $cardnum
     * @return string
     * @author 陈妙威
     */
    public static function decryptIdentity($cardnum)
    {
        $cardnum = str_replace('x', "X", $cardnum);
        //    $cardnum = "nnnnnn810607021";
        if (strlen(strval(intval($cardnum))) === 15) {
            $i = substr($cardnum, 12, 2);
            $i = str_split($i);
            $i = array_sum($i);
            $i = $i % 10 + 1;


            $nian = substr($cardnum, 6, 2);
            $nian = intval($nian);
            if ($nian < $i) {
                $nian = $nian + 100 - $i;
            } else {
                $nian = $nian - $i;
            }
            $nian = strlen(strval($nian)) === 1 ? "0" . strval($nian) : strval($nian);

            $yue = substr($cardnum, 8, 2);
            $yue = intval($yue);
            if ($yue < $i) {
                $yue = $yue + 12 - $i;
            } else {
                $yue = $yue - $i;
            }
            $yue = strlen(strval($yue)) === 1 ? "0" . strval($yue) : strval($yue);

            $ri = substr($cardnum, 10, 2);
            $ri = intval($ri);
            if ($ri < $i) {
                $ri = $ri + 31 - $i;
            } else {
                $ri = $ri - $i;
            }
            $ri = strlen(strval($ri)) === 1 ? "0" . strval($ri) : strval($ri);

            $cardnum = substr($cardnum, 0, 6) . $nian . $yue . $ri . substr($cardnum, 12, 3);
        }

        if (strlen(strval(intval($cardnum))) === 18) {
            //            $cardnum = "nnnnnn19040704026X";
            $i = substr($cardnum, 14, 3);
            $i = str_split($i);
            $i = array_sum($i);
            $i = $i % 10 + 1;


            $nian = substr($cardnum, 8, 2);
            $nian = intval($nian);
            if ($nian < $i) {
                $nian = $nian + 100 - $i;
            } else {
                $nian = $nian - $i;
            }
            $nian = strlen(strval($nian)) === 1 ? "0" . strval($nian) : strval($nian);

            $yue = substr($cardnum, 10, 2);
            $yue = intval($yue);
            if ($yue <= $i) {
                $yue = $yue + 12 - $i;
            } else {
                $yue = $yue - $i;
            }
            $yue = strlen(strval($yue)) === 1 ? "0" . strval($yue) : strval($yue);

            $ri = substr($cardnum, 12, 2);
            $ri = intval($ri);
            if ($ri <= $i) {
                $ri = $ri + 31 - $i;
            } else {
                $ri = $ri - $i;
            }
            $ri = strlen(strval($ri)) === 1 ? "0" . strval($ri) : strval($ri);

            $cardnum = substr($cardnum, 0, 8) . $nian . $yue . $ri . substr($cardnum, 14, 4);
        }

        return $cardnum;
    }
}