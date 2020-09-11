<?php
// +----------------------------------------------------------------------
// | [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: daydayin <huangminhu@gmail.com>
// +----------------------------------------------------------------------
// | Date: 2018/4/22 11:43
// +----------------------------------------------------------------------

namespace app\common\helper;

use think\Exception;

/**
 * 数组处理类
 *
 * Class ArrayHelper
 * @package app\common\helper
 */
class ArrayHelper
{
    /**
     * 将对象或对象数组转换为数组.
     *
     * @param object|array|string $object 要转换为数组的对象
     * @param array $properties 从对象类名到需要放入结果数组的属性的映射。
     *                          为每个类指定的属性是以下格式的数组:
     *
     * @param boolean $recursive 是否递归地将对象属性转换为数组.
     *
     * @return array the array representation of the object
     */
    public static function toArray($object, $properties = [], $recursive = true)
    {
        if (\is_array($object)) {
            if ($recursive) {
                foreach ($object as $key => $value) {
                    if (\is_array($value) || \is_object($value)) {
                        $object[$key] = static::toArray($value, $properties, true);
                    }
                }
            }
            return $object;
        } elseif (\is_object($object)) {
            if (!empty($properties)) {
                $className = \get_class($object);
                if (!empty($properties[$className])) {
                    $result = [];
                    foreach ($properties[$className] as $key => $name) {
                        if (\is_int($key)) {
                            $result[$name] = $object->$name;
                        } else {
                            $result[$key] = static::getValue($object, $name);
                        }
                    }

                    return $recursive ? static::toArray($result, $properties) : $result;
                }
            }
            if (method_exists($object, 'toArray')) {
                $result = $object->toArray([], [], $recursive);
            } else {
                $result = [];
                foreach ($object as $key => $value) {
                    $result[$key] = $value;
                }
            }
            return $recursive ? static::toArray($result, $properties) : $result;
        } else {
            return [$object];
        }
    }

    /**
     * 递归地合并两个或多个数组。
     * 如果每个数组都有一个具有相同字符串键值的元素，则是后者将覆盖前者
     * (不同于array_merge_recursive, 追加的)。
     * 如果两个数组都有数组元素，则进行递归合并类型和拥有相同的键。
     * 对于整数键控元素，后一个数组中的元素将被附加到前一个数组。
     *
     * @param array $a 要合并到的数组
     * @param array $b 数组合并。您可以指定其他数组通过第三个参数，第四个参数等
     *
     * @return array the merged array (the original arrays are not changed.)
     */
    public static function merge($a, $b)
    {
        $args = \func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if (\is_int($k)) {
                    if (isset($res[$k])) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (\is_array($v) && isset($res[$k]) && \is_array($res[$k])) {
                    $res[$k] = self::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }

    /**
     * 检索具有给定键或属性名的数组元素或对象属性的值。
     * 如果数组或对象中不存在键，则返回默认值。
     *
     * 键可以用点格式指定，以检索子数组或属性的值
     * 内嵌对象的。特别是，如果键是“x.y”。，则返回值为
     * 是“$array['x']['y']['z']'或' $array->x->y->z”(如果$array是对象)。如果$array(“x”)的
     * 或“$array->x”既不是数组也不是对象，将返回默认值。
     * 注意，如果数组中已经有一个元素' x.y '。，则返回其值
     * 而不是遍历子数组。因此，最好指定一个键名数组
     * 例如“['x'，'y'，'z']”。
     *
     * 下面是一些用法示例
     *
     * ```php
     * // working with array
     * $username = \Swoft\Helper\ArrayHelper::getValue($_POST, 'username');
     * // working with object
     * $username = \Swoft\Helper\ArrayHelper::getValue($user, 'username');
     * // working with anonymous function
     * $fullName = \Swoft\Helper\ArrayHelper::getValue($user, function ($user, $defaultValue) {
     *     return $user->firstName . ' ' . $user->lastName;
     * });
     * // using dot format to retrieve the property of embedded object
     * $street = \Swoft\Helper\ArrayHelper::getValue($users, 'address.street');
     * // using an array of keys to retrieve the value
     * $value = \Swoft\Helper\ArrayHelper::getValue($versions, ['1.0', 'date']);
     * ```
     *
     * @param array|object $array 要从中提取值的数组或对象
     * @param string|\Closure|array $key 数组元素的键名、键的数组或对象的属性名,或者返回值的匿名函数。匿名函数签名应该是`function($array, $defaultValue)`.
     * @param mixed $default 如果指定的数组键不存在，将返回的默认值。从对象获取值时不使用.
     *
     * @return mixed 如果找到该元素的值，则默认值为else
     */
    public static function getValue($array, $key, $default = null)
    {
        if ($key instanceof \Closure) {
            return $key($array, $default);
        }

        if (\is_array($key)) {
            $lastKey = array_pop($key);
            foreach ($key as $keyPart) {
                $array = static::getValue($array, $keyPart);
            }
            $key = $lastKey;
        }

        if (\is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
            return $array[$key];
        }

        if (($pos = strrpos($key, '.')) !== false) {
            $array = static::getValue($array, substr($key, 0, $pos), $default);
            $key = (string)substr($key, $pos + 1);
        }

        if (\is_object($array)) {
            // this is expected to fail if the property does not exist, or __get() is not implemented
            // it is not reliably possible to check whether a property is accessable beforehand
            return $array->$key;
        }

        if (\is_array($array)) {
            return (isset($array[$key]) || array_key_exists($key, $array)) ? $array[$key] : $default;
        }

        return $default;
    }

    /**
     * 从数组中移除项目并返回值。如果该键不存在于数组中，则为默认值将被返回
     *
     * 用法示例,
     *
     * ```php
     * // $array = ['type' => 'A', 'options' => [1, 2]];
     * // working with array
     * $type = \Swoft\Helper\ArrayHelper::remove($array, 'type');
     * // $array content
     * // $array = ['options' => [1, 2]];
     * ```
     *
     * @param array $array 要从中提取值的数组
     * @param string $key 数组元素的键名
     * @param mixed $default 如果指定的键不存在，将返回的默认值
     *
     * @return mixed|null 如果找到该元素的值，则默认值为else
     */
    public static function remove(&$array, $key, $default = null)
    {
        if (\is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
            $value = $array[$key];
            unset($array[$key]);

            return $value;
        }

        return $default;
    }

    /**
     * 使用“点”符号从给定数组中删除一个或多个数组项
     *
     * @param  array $array
     * @param  array|string $keys
     *
     * @return void
     */
    public static function forget(&$array, $keys)
    {
        $original = &$array;

        $keys = (array)$keys;

        if (\count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (\count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && \is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * 从数组中获取一个值，并删除它.
     *
     * @param  array $array
     * @param  string $key
     * @param  mixed $default
     *
     * @return mixed
     */
    public static function pull(&$array, $key, $default = null)
    {
        $value = static::get($array, $key, $default);

        static::forget($array, $key);

        return $value;
    }

    /**
     * 根据指定的键对数组进行索引和/或分组。
     * 输入应该是多维数组或对象数组。
     *
     * $key可以是子数组的键名、对象的属性名，也可以是匿名的函数，该函数必须返回将用作键的值。
     *
     * $groups是一个键数组，用于将输入数组分组为一个或多个子数组在指定的键上。
     *
     * 如果“$key”被指定为“null”，或者与该键对应的元素的值为“null”
     * 若未指定' $groups '，则元素将被丢弃。
     *
     * 例如:
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'Data' => 'abc', 'device' => 'laptop'],
     *     ['id' => '345', 'Data' => 'def', 'device' => 'tablet'],
     *     ['id' => '345', 'Data' => 'hgi', 'device' => 'smartphone'],
     * ];
     * $result = ArrayHelper::index($array, 'id');
     * ```
     *
     * 结果将是一个关联数组，其中的键是' id '属性的值
     *
     * ```php
     * [
     *     '123' => ['id' => '123', 'Data' => 'abc', 'device' => 'laptop'],
     *     '345' => ['id' => '345', 'Data' => 'hgi', 'device' => 'smartphone']
     *     // 由于相同的id，原始数组的第二个元素被最后一个元素覆盖
     * ]
     * ```
     *
     * 分组数组中也可以使用匿名函数.
     *
     * ```php
     * $result = ArrayHelper::index($array, function ($element) {
     *     return $element['id'];
     * });
     * ```
     *
     * 通过“id”系列，将“$array”通过“id”:
     *
     * ```php
     * $result = ArrayHelper::index($array, null, 'id');
     * ```
     *
     * 结果将是一个多维数组，按第一层的“id”分组，按第二层的“device”分组按“数据”在第三层建立索引:
     *
     *
     * ```php
     * [
     *     '123' => [
     *         ['id' => '123', 'Data' => 'abc', 'device' => 'laptop']
     *     ],
     *     '345' => [ // 具有该索引的所有元素都出现在结果数组中
     *         ['id' => '345', 'Data' => 'def', 'device' => 'tablet'],
     *         ['id' => '345', 'Data' => 'hgi', 'device' => 'smartphone'],
     *     ]
     * ]
     * ```
     *
     * 匿名函数也可用于分组键数组:
     *
     * ```php
     * $result = ArrayHelper::index($array, 'Data', [function ($element) {
     *     return $element['id'];
     * }, 'device']);
     * ```
     *
     * The result will be a multidimensional array grouped by `id` on the first level, by the `device` on the second one
     * and indexed by the `Data` on the third level:
     *
     * ```php
     * [
     *     '123' => [
     *         'laptop' => [
     *             'abc' => ['id' => '123', 'Data' => 'abc', 'device' => 'laptop']
     *         ]
     *     ],
     *     '345' => [
     *         'tablet' => [
     *             'def' => ['id' => '345', 'Data' => 'def', 'device' => 'tablet']
     *         ],
     *         'smartphone' => [
     *             'hgi' => ['id' => '345', 'Data' => 'hgi', 'device' => 'smartphone']
     *         ]
     *     ]
     * ]
     * ```
     *
     * @param array $array the array that needs to be indexed or grouped
     * @param string|\Closure|null $key the column name or anonymous function which result will be used to index the array
     * @param string|string[]|\Closure[]|null $groups the array of keys, that will be used to group the input array
     *                                                by one or more keys. If the $key attribute or its value for the particular element is null and $groups is not
     *                                                defined, the array element will be discarded. Otherwise, if $groups is specified, array element will be added
     *                                                to the result array without any key.
     *
     * @return array the indexed and/or grouped array
     */
    public static function index($array, $key, $groups = [])
    {
        $result = [];
        $groups = (array)$groups;

        foreach ($array as $element) {
            $lastArray = &$result;

            foreach ($groups as $group) {
                $value = static::getValue($element, $group);
                if (!array_key_exists($value, $lastArray)) {
                    $lastArray[$value] = [];
                }
                $lastArray = &$lastArray[$value];
            }

            if ($key === null) {
                if (!empty($groups)) {
                    $lastArray[] = $element;
                }
            } else {
                $value = static::getValue($element, $key);
                if ($value !== null) {
                    if (\is_float($value)) {
                        $value = (string)$value;
                    }
                    $lastArray[$value] = $element;
                }
            }
            unset($lastArray);
        }

        return $result;
    }

    /**
     * 返回数组中指定列的值。
     * 输入数组应为多维数组或对象数组。
     *
     * For example,
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'Data' => 'abc'],
     *     ['id' => '345', 'Data' => 'def'],
     * ];
     * $result = ArrayHelper::getColumn($array, 'id');
     * // the result is: ['123', '345']
     *
     * // using anonymous function
     * $result = ArrayHelper::getColumn($array, function ($element) {
     *     return $element['id'];
     * });
     * ```
     *
     * @param array $array
     * @param string|\Closure $name
     * @param boolean $keepKeys whether to maintain the array keys. If false, the resulting array
     *                                  will be re-indexed with integers.
     *
     * @return array the list of column values
     */
    public static function getColumn($array, $name, $keepKeys = true)
    {
        $result = [];
        if ($keepKeys) {
            foreach ($array as $k => $element) {
                $result[$k] = static::getValue($element, $name);
            }
        } else {
            foreach ($array as $element) {
                $result[] = static::getValue($element, $name);
            }
        }

        return $result;
    }

    /**
     * 从多维数组或对象数组构建映射(键-值对)。
     * “$from”和“$to”参数指定要设置映射的键名或属性名。
     * 也可以根据分组字段“$group”进一步分组映射。
     *
     * For example,
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'name' => 'aaa', 'class' => 'x'],
     *     ['id' => '124', 'name' => 'bbb', 'class' => 'x'],
     *     ['id' => '345', 'name' => 'ccc', 'class' => 'y'],
     * ];
     *
     * $result = ArrayHelper::map($array, 'id', 'name');
     * // the result is:
     * // [
     * //     '123' => 'aaa',
     * //     '124' => 'bbb',
     * //     '345' => 'ccc',
     * // ]
     *
     * $result = ArrayHelper::map($array, 'id', 'name', 'class');
     * // the result is:
     * // [
     * //     'x' => [
     * //         '123' => 'aaa',
     * //         '124' => 'bbb',
     * //     ],
     * //     'y' => [
     * //         '345' => 'ccc',
     * //     ],
     * // ]
     * ```
     *
     * @param array $array
     * @param string|\Closure $from
     * @param string|\Closure $to
     * @param string|\Closure $group
     *
     * @return array
     */
    public static function map($array, $from, $to, $group = null)
    {
        $result = [];
        foreach ($array as $element) {
            $key = static::getValue($element, $from);
            $value = static::getValue($element, $to);
            if ($group !== null) {
                $result[static::getValue($element, $group)][$key] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 检查给定数组是否包含指定键。
     * 这个方法通过支持不区分大小写来增强' array_key_exists() '函数键比较.
     *
     * @param string $key the key to check
     * @param array $array the array with keys to check
     * @param boolean $caseSensitive whether the key comparison should be case-sensitive
     *
     * @return boolean whether the array contains the specified key
     */
    public static function keyExists($key, $array, $caseSensitive = true)
    {
        if ($caseSensitive) {
            return array_key_exists($key, $array);
        } else {
            foreach (array_keys($array) as $k) {
                if (strcasecmp($key, $k) === 0) {
                    return true;
                }
            }

            return false;
        }
    }

    /**
     * 按一个或多个键对对象数组或数组(具有相同结构)进行排序
     *
     * @param array $array the array to be sorted. The array will be modified after calling this method.
     * @param string|\Closure|array $key the key(s) to be sorted by. This refers to a key name of the sub-array
     *                                         elements, a property name of the objects, or an anonymous function returning the values for comparison
     *                                         purpose. The anonymous function signature should be: `function($item)`.
     *                                         To sort by multiple keys, provide an array of keys here.
     * @param integer|array $direction the sorting direction. It can be either `SORT_ASC` or `SORT_DESC`.
     *                                         When sorting by multiple keys with different sorting directions, use an array of sorting directions.
     * @param integer|array $sortFlag the PHP sort flag. Valid values include
     *                                         `SORT_REGULAR`, `SORT_NUMERIC`, `SORT_STRING`, `SORT_LOCALE_STRING`, `SORT_NATURAL` and `SORT_FLAG_CASE`.
     *                                         Please refer to [PHP manual](http://php.net/manual/zh/function.sort.php)
     *                                         for more details. When sorting by multiple keys with different sort flags, use an array of sort flags.
     *
     * @throws InvalidParamException if the $direction or $sortFlag parameters do not have
     * correct number of elements as that of $key.
     */
    public static function multisort(&$array, $key, $direction = SORT_ASC, $sortFlag = SORT_REGULAR)
    {
        $keys = \is_array($key) ? $key : [$key];
        if (empty($keys) || empty($array)) {
            return;
        }
        $n = \count($keys);
        if (is_scalar($direction)) {
            $direction = array_fill(0, $n, $direction);
        } elseif (\count($direction) !== $n) {
            exception('The length of $direction parameter must be the same as that of $keys.', 1);
        }
        if (is_scalar($sortFlag)) {
            $sortFlag = array_fill(0, $n, $sortFlag);
        } elseif (\count($sortFlag) !== $n) {
            exception('The length of $sortFlag parameter must be the same as that of $keys.', 1);
        }
        $args = [];
        foreach ($keys as $i => $key) {
            $flag = $sortFlag[$i];
            $args[] = static::getColumn($array, $key);
            $args[] = $direction[$i];
            $args[] = $flag;
        }

        // This fix is used for cases when main sorting specified by columns has equal values
        // Without it it will lead to Fatal Error: Nesting level too deep - recursive dependency?
        $args[] = range(1, \count($array));
        $args[] = SORT_ASC;
        $args[] = SORT_NUMERIC;

        $args[] = &$array;
        \call_user_func_array('array_multisort', $args);
    }

    /**
     * 返回一个值，该值指示给定数组是否是关联数组。
     *
     * 如果数组的键都是字符串，那么数组就是关联的。如果' $allStrings '为假，
     * 如果一个数组的键中至少有一个是字符串，那么该数组将被视为关联数组。
     *
     * 注意，空数组不会被认为是关联的。
     *
     * @param array $array 被检查的数组
     * @param boolean $allStrings 数组键是否必须为所有字符串将被视为关联的数组
     *
     * @return boolean whether the array is associative
     */
    public static function isAssociative($array, $allStrings = true)
    {
        if (!\is_array($array) || empty($array)) {
            return false;
        }

        if ($allStrings) {
            foreach ($array as $key => $value) {
                if (!\is_string($key)) {
                    return false;
                }
            }

            return true;
        } else {
            foreach ($array as $key => $value) {
                if (\is_string($key)) {
                    return true;
                }
            }

            return false;
        }
    }

    /**
     * 返回一个值，该值指示给定数组是否是索引数组。
     *
     * 如果一个数组的所有键都是整数，那么该数组将被索引。如果“$ continuity”为真，
     * 那么数组键必须是从0开始的连续序列。
     *
     * 注意，空数组将被视为索引。
     *
     * @param array $array the array being checked
     * @param boolean $consecutive whether the array keys must be a consecutive sequence
     *                             in order for the array to be treated as indexed.
     *
     * @return boolean whether the array is associative
     */
    public static function isIndexed($array, $consecutive = false)
    {
        if (!\is_array($array)) {
            return false;
        }

        if (empty($array)) {
            return true;
        }

        if ($consecutive) {
            return array_keys($array) === range(0, \count($array) - 1);
        } else {
            foreach ($array as $key => $value) {
                if (!\is_int($key)) {
                    return false;
                }
            }

            return true;
        }
    }

    /**
     * 检查数组或[[\可遍历]]是否包含元素。
     *
     * This method does the same as the PHP function [in_array()](http://php.net/manual/en/function.in-array.php)
     * but additionally works for objects that implement the [[\Traversable]] interface.
     *
     * @param mixed $needle 寻找的值.
     * @param array|\Traversable $haystack 搜索的目标(数组或者实现了Traversable的对象).
     * @param boolean $strict Whether to enable strict (`===`) comparison.
     *
     * @return boolean `true` if `$needle` was found in `$haystack`, `false` otherwise.
     * @see   http://php.net/manual/en/function.in-array.php
     */
    public static function isIn($needle, $haystack, $strict = false)
    {
        if ($haystack instanceof \Traversable) {
            foreach ($haystack as $value) {
                if ($needle == $value && (!$strict || $needle === $value)) {
                    return true;
                }
            }
        } elseif (\is_array($haystack)) {
            return \in_array($needle, $haystack, $strict);
        } else {
            exception('Argument $haystack must be an array or implement Traversable', 1);
        }

        return false;
    }

    /**
     * 检查一个变量是数组还是[[\可遍历]]。
     *
     * This method does the same as the PHP function [is_array()](http://php.net/manual/en/function.is-array.php)
     * but additionally works on objects that implement the [[\Traversable]] interface.
     *
     * @param mixed $var The variable being evaluated.
     *
     * @return boolean whether $var is array-like
     * @see   http://php.net/manual/en/function.is_array.php
     */
    public static function isTraversable($var)
    {
        return \is_array($var) || $var instanceof \Traversable;
    }

    /**
     * 检查一个数组或[[\可遍历]]是否是另一个数组或[[\可遍历]]的子集。
     *
     * 如果“$needle”的所有元素都包含在该方法中，则该方法将返回“true”
     * “干草堆美元”。如果至少缺少一个元素，将返回“false”。
     *
     * @param array|\Traversable $needles The values that must **all** be in `$haystack`.
     * @param array|\Traversable $haystack The set of value to search.
     * @param boolean $strict Whether to enable strict (`===`) comparison.
     *
     * @throws exception if `$haystack` or `$needles` is neither traversable nor an array.
     * @return boolean `true` if `$needles` is a subset of `$haystack`, `false` otherwise.
     */
    public static function isSubset($needles, $haystack, $strict = false)
    {
        if (\is_array($needles) || $needles instanceof \Traversable) {
            foreach ($needles as $needle) {
                if (!static::isIn($needle, $haystack, $strict)) {
                    return false;
                }
            }

            return true;
        } else {
            exception('Argument $needles must be an array or implement Traversable', 1);
        }
    }

    /**
     * 根据指定的规则筛选数组.
     *
     * For example:
     * ```php
     * $array = [
     *     'A' => [1, 2],
     *     'B' => [
     *         'C' => 1,
     *         'D' => 2,
     *     ],
     *     'E' => 1,
     * ];
     *
     * $result = \Swoft\Helper\ArrayHelper::Filter($array, ['A']);
     * // $result will be:
     * // [
     * //     'A' => [1, 2],
     * // ]
     *
     * $result = \Swoft\Helper\ArrayHelper::Filter($array, ['A', 'B.C']);
     * // $result will be:
     * // [
     * //     'A' => [1, 2],
     * //     'B' => ['C' => 1],
     * // ]
     * ```
     *
     * $result = \Swoft\Helper\ArrayHelper::Filter($array, ['B', '!B.C']);
     * // $result will be:
     * // [
     * //     'B' => ['D' => 2],
     * // ]
     * ```
     *
     * @param array $array Source array
     * @param array $filters Rules that define array keys which should be left or removed from results.
     *                       Each rule is:
     *                       - `var` - `$array['var']` will be left in result.
     *                       - `var.key` = only `$array['var']['key'] will be left in result.
     *                       - `!var.key` = `$array['var']['key'] will be removed from result.
     *
     * @return array Filtered array
     */
    public static function filter($array, $filters)
    {
        $result = [];
        $forbiddenVars = [];

        foreach ($filters as $var) {
            $keys = explode('.', $var);
            $globalKey = $keys[0];
            $localKey = $keys[1] ?? null;

            if ($globalKey[0] === '!') {
                $forbiddenVars[] = [
                    substr($globalKey, 1),
                    $localKey,
                ];
                continue;
            }

            if (empty($array[$globalKey])) {
                continue;
            }
            if ($localKey === null) {
                $result[$globalKey] = $array[$globalKey];
                continue;
            }
            if (!isset($array[$globalKey][$localKey])) {
                continue;
            }
            if (!array_key_exists($globalKey, $result)) {
                $result[$globalKey] = [];
            }
            $result[$globalKey][$localKey] = $array[$globalKey][$localKey];
        }

        foreach ($forbiddenVars as $var) {
            list($globalKey, $localKey) = $var;
            if (array_key_exists($globalKey, $result)) {
                unset($result[$globalKey][$localKey]);
            }
        }

        return $result;
    }

    /**
     * 确定给定的值是否数组可访问。
     *
     * @param  mixed $value
     *
     * @return bool
     */
    public static function accessible($value)
    {
        return \is_array($value) || $value instanceof \ArrayAccess;
    }

    /**
     * 确定给定键是否存在于提供的数组中。
     *
     * @param  \ArrayAccess|array $array
     * @param  string|int $key
     *
     * @return bool
     */
    public static function exists($array, $key)
    {
        if (\is_array($array)) {
            return array_key_exists($key, $array);
        }

        return $array->offsetExists($key);
    }

    /**
     * 使用“点”符号从数组中获取项目。
     *
     * @param  \ArrayAccess|array $array
     * @param  string $key
     * @param  mixed $default
     *
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (null === $key) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * 使用“点”符号检查数组中是否存在项。
     * 批量检测数组中多个键是否存在
     *
     * @param  \ArrayAccess|array $array
     * @param  string $key
     *
     * @return bool
     */
    public static function has($array, $key)
    {
        if (empty($array) || null === $key) {
            return false;
        }

        if (array_key_exists($key, $array)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if ((\is_array($array) && array_key_exists($segment, $array)) || ($array instanceof \ArrayAccess && $array->offsetExists($segment))) {
                $array = $array[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * 使用“点”符号将数组项设置为给定值。
     * 如果没有给方法键，整个数组将被替换。
     *
     * @param  array $array
     * @param  string $key
     * @param  mixed $value
     *
     * @return array
     */
    public static function set(&$array, $key, $value)
    {
        if (null === $key) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (\count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !\is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * 从数组中删除空白的元素（包括只有空白字符的元素）
     *
     * 用法：
     * @code php
     * $arr = array('', 'test', '  ');
     * ArrayHelper::removeEmpty($arr);
     *
     * dump($arr);
     *  // 输出结果中将只有 'test'
     * @endcode
     *
     * @param array $arr 要处理的数组
     * @param boolean $trim 是否对数组元素调用 trim 函数
     */
    static function removeEmpty(&$arr, $trim = true)
    {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                self::removeEmpty($arr[$key]);
            } else {
                $value = trim($value);
                if ($value == '') {
                    unset($arr[$key]);
                } elseif ($trim) {
                    $arr[$key] = $value;
                }
            }
        }
    }

    /**
     * 将一个平面的二维数组按照指定的字段转换为树状结构
     *
     * 如果要获得任意节点为根的子树，可以使用 $refs 参数：
     * @code php
     * $refs = null;
     * $tree = ArrayHelper::tree($rows, 'id', 'parent', 'nodes', $refs);
     *
     * // 输出 id 为 3 的节点及其所有子节点
     * $id = 3;
     * dump($refs[$id]);
     * @endcode
     *
     * return array 树形结构的数组
     *
     * @param $arr
     * @param string $keyId 节点ID字段名
     * @param string $keyParentId 节点父ID字段名
     * @param string $keyChildrens 保存子节点的字段名
     * @param null $refs 是否在返回结果中包含节点引用
     * @return array
     */
    static function toTree($arr, $keyId = 'id', $keyParentId = 'parentid', $keyChildrens = 'childrens', &$refs = null)
    {
        $refs = [];
        foreach ($arr as $offset => $row) {
            $arr[$offset][$keyChildrens] = [];
            $refs[$row[$keyId]] = &$arr[$offset];
        }
        $tree = [];
        foreach ($arr as $offset => $row) {
            $parentId = $row[$keyParentId];
            if ($parentId) {
                if (!isset($refs[$parentId])) {
                    $tree[] = &$arr[$offset];
                    continue;
                }
                $parent = &$refs[$parentId];
                $parent[$keyChildrens][] = &$arr[$offset];
            } else {
                $tree[] = &$arr[$offset];
            }
        }
        return $tree;
    }

    /**
     * 将树形数组展开为平面的数组
     *
     * 这个方法是 tree() 方法的逆向操作。
     *
     * @param array $tree 树形数组
     * @param string $keyChildrens 包含子节点的键名
     *
     * @return array 展开后的数组
     */
    static function treeToArray($tree, $keyChildrens = 'childrens')
    {
        $ret = array();
        if (isset($tree[$keyChildrens]) && is_array($tree[$keyChildrens])) {
            foreach ($tree[$keyChildrens] as $child) {
                $ret = array_merge($ret, self::treeToArray($child, $keyChildrens));
            }
            unset($tree[$keyChildrens]);
            $ret[] = $tree;
        } else {
            $ret[] = $tree;
        }
        return $ret;
    }

    /**
     * 格式化列表
     * @param $list
     * @return array
     * For example:
     *
     * ```php
     * $array = [
     *     'title' => ['123', '456', '789'],
     *     'url' => ['abc', 'def', 'hig'],
     *     'time' => ['111', '222', '333'],
     * ];
     * $result = SiteHelper::listToArray($array);
     * ```
     *
     * The result will be an associative array, where the key is the value of `id` attribute
     *
     * ```php
     * [
     *     ['title' => '123', 'url' => 'abc', 'time' => '111'],
     *     ['title' => '456', 'url' => 'def', 'time' => '222'],
     *     ['title' => '789', 'url' => 'hig', 'time' => '333'],
     * ]
     * ```
     */
    public static function listToArray($list)
    {
        $data = [];
        if (!empty($list)) {
            $keys = array_keys($list);
            foreach ($keys as $k => $v) {
                for ($i = 0; $i < count($list[$v]); $i++) {
                    $data[$i][$v] = $list[$v][$i];
                }
            }
        }
        return (array)$data;
    }

    /**
     * 格式化自定义参数
     * @param $params
     * @return array
     * For example:
     *
     * ```php
     * $array = [
     *     ['key' => '123', 'value' => 'abc'],
     *     ['key' => '345', 'value' => 'def'],
     *     ['key' => '345', 'value' => 'hgi'],
     * ];
     * $result = SiteHelper::formatParams($array);
     * ```
     *
     * The result will be an associative array, where the key is the value of `id` attribute
     *
     * ```php
     * [
     *     '123' => 'abc',
     *     '345' => 'hgi'
     *     // The second element of an original array is overwritten by the last element because of the same id
     * ]
     * ```
     */
    public static function formatParams($params)
    {
        $setting = [];
        foreach ($params as $v) {
            if (!$v['key']) continue;
            $setting[$v['key']] = $v['value'];
        }
        return $setting;
    }

    /**
     * 将一个二维数组转换为 HashMap，并返回结果
     *
     * 用法1：
     * @code php
     * $rows = array(
     *   array('id' => 1, 'value' => '1-1'),
     *   array('id' => 2, 'value' => '2-1'),
     * );
     * $hashmap = ArrayHelper::toHashmap($rows, 'id', 'value');
     *
     * dump($hashmap);
     *  // 输出结果为
     *  // array(
     *  //  1 => '1-1',
     *  //  2 => '2-1',
     *  // )
     * @endcode
     *
     * 如果省略 $valueField 参数，则转换结果每一项为包含该项所有数据的数组。
     *
     * 用法2：
     * @code php
     * $rows = array(
     *   array('id' => 1, 'value' => '1-1'),
     *   array('id' => 2, 'value' => '2-1'),
     * );
     * $hashmap = ArrayHelper::toHashmap($rows, 'id');
     *
     * dump($hashmap);
     *  // 输出结果为
     *  // array(
     *  //  1 => array('id' => 1, 'value' => '1-1'),
     *  //  2 => array('id' => 2, 'value' => '2-1'),
     *  // )
     * @endcode
     *
     * @param array $arr 数据源
     * @param string $keyField 按照什么键的值进行转换
     * @param string $valueField 对应的键值
     *
     * @return array 转换后的 HashMap 样式数组
     */
    public static function toHashmap($arr, $keyField, $valueField = null)
    {
        $ret = [];
        if ($valueField) {
            foreach ($arr as $row) {
                $ret[$row[$keyField]] = $row[$valueField];
            }
        } else {
            foreach ($arr as $row) {
                $ret[$row[$keyField]] = $row;
            }
        }
        return $ret;
    }


    //----- 迁移分界线

    /**
     * 从数组中用值找key
     *
     * @param string $value
     * @param array $array
     * @param string $default
     * @return int|string
     */
    public static function findKeyUseValue($value, $array, $default = '')
    {
        if (!$value) {
            return $default;
        }

        foreach ($array as $key => $val) {
            if ($value == $val) {
                return $key;
            }
        }

        return $default;
    }

    /**
     * 寻找出只在该数组中的键值
     *
     * @param array $array
     * @param array|string $only
     * @return array
     */
    public static function only($array, $only = [])
    {
        if (!is_array($only)) {
            $only = explode(',', $only);
        }

        $ret = [];
        foreach ($array as $key => $value) {
            if (in_array($key, $only)) {
                $ret[$key] = $value;
            }
        }

        return $ret;
    }

    /**
     * 寻找出不在该数组中的键值
     *
     * @param array $array
     * @param array|string $only
     * @return array
     */
    public static function except($array, $only = [])
    {
        if (!is_array($only)) {
            $only = explode(',', $only);
        }

        $ret = [];
        foreach ($array as $key => $value) {
            if (!in_array($key, $only)) {
                $ret[$key] = $value;
            }
        }

        return $ret;
    }

    /**
     * 从数组中找到某列的值
     *
     * @param array $array
     * @param string $field
     * @return array
     */
    public static function column(&$array, $field)
    {
        $ret = [];
        foreach ($array as $item) {
            $ret[] = $item[$field];
        }

        return $ret;
    }

    /**
     * 数组按指定的key重新分组
     *
     * @param array $array
     * @param string $field
     * @return array
     */
    public static function group($array, $field)
    {
        $ret = [];
        foreach ($array as $item) {
            $ret[$item[$field]][] = $item;
        }

        return $ret;
    }

    /**
     * 数组按指定的key重新分组获取指定key的值
     *
     * @param array $array
     * @param string $groupKey
     * @param string $columnKey
     * @return array
     */
    public static function groupColumns(&$array, $groupKey, $columnKey)
    {
        $ret = [];
        foreach ($array as $item) {
            $ret[$item[$groupKey]][] = $item[$columnKey];
        }

        return $ret;
    }

    /**
     * 从二维数组中取出自己要的KEY值
     *
     * @param  array $arrData
     * @param string $key
     * @param        $im true 返回逗号分隔
     *
     * @return array|string
     */
    public static function filterValue($arrData, $key, $im = false)
    {
        $re = [];
        foreach ($arrData as $k => $v) {
            if (isset($v[$key])) {
                $re[] = $v[$key];
            }
        }
        if (!empty($re)) {
            $re = array_flip(array_flip($re));
            sort($re);
        }
        return $im ? implode(',', $re) : $re;
    }

    /**
     * 删除多维数组中某个值
     *
     * @param $array
     * @param $key
     * @param string $child
     * @return mixed
     */
    public static function delArrayKey(&$array, $key, $child = 'children')
    {
        foreach ($array as &$item) {
            unset($item[$key]);
            if (is_array($item[$child])) {
                self::delArrayKey($item[$child], $key, $child);
            }
        }

        return $array;
    }


    public static function treeArrayMaps(array $trees, $child = 'children')
    {
        $result = [];
        foreach ($trees as $tree) {
            $data = [];
            $stack = [];
            self::treePaths($tree, $child, $data, $stack);
            foreach ($data as &$value) {
                foreach ($value as &$item) {
                    unset($item[$child]);
                }
            }
            $result = array_merge($result, $data);
        }
        return $result;
    }


    public static function treePaths(array &$tree, $child = 'children', &$result = [], &$stack = [])
    {

        if (!$tree) {
            return $stack;
        }
        $stack[] = $tree;
        if (!$tree[$child]) {
            //打印当前路径
            $pathNodes = $stack;
            $result = array_merge($result, [$pathNodes]);
            $endNode = end($pathNodes);
            array_pop($stack);
            if (count($stack) > 0){
                $parent = &$stack[count($stack)-1];
                if ($parent) {
                    foreach ($parent[$child] as $key => $item) {
                        if ($item['id'] == $endNode['id']) {
                            unset($parent[$child][$key]);
                            break;
                        }
                    }
                }
                if (count($parent[$child]) == 0) {
                    array_pop($stack);
                }
            }
        } else {
            foreach ($tree[$child] as $key => &$value) {
                self::treePaths($value, $child, $result, $stack);
            }
        }
    }

}
