<?php
// +----------------------------------------------------------------------
// | [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: daydayin <huangminhu@gmail.com>
// +----------------------------------------------------------------------
// | Date: 2018/4/27 09:40
// +----------------------------------------------------------------------

namespace app\common\helper;

use think\Db;
use think\exception\ValidateException;

/**
 * sql语句条件类
 *
 * Class WhereHelper
 * @package app\common\helper
 */
class WhereHelper
{
    /**
     * 小于值
     *
     * @param $field
     * @param $val
     * @param bool $retarr
     * @return array|string
     */
    public static function min($field, $val, $retarr = true)
    {
        if (!$val) return;

        if ($retarr) {
            $where[] = [$field, '>=', $val];
        } else {
            $where = "{$field} >= {$val}";
        }
        return $where;
    }

    /**
     * 大于值
     *
     * @param $field
     * @param $val
     * @param bool $retarr
     * @return array|string|void
     */
    public static function max($field, $val, $retarr = true)
    {
        if (!$val) return;

        if ($retarr) {
            $where[] = [$field, '<=', $val];
        } else {
            $where = "{$field} <= {$val}";
        }
        return $where;
    }

    /**
     * 时间小于
     *
     * @param $field
     * @param $minTime
     * @param bool $retarr
     * @return array|string|void
     */
    public static function minTime($field, $minTime, $retarr = true)
    {
        if (!$minTime) return;
        $minTime = trim($minTime);
        if (!is_numeric($minTime)) {
            if (strlen($minTime) == 10) $minTime .= ' 00:00:00';
            $minTime = strtotime($minTime);
        }

        if ($retarr) {
            $where[] = [$field, '>=', $minTime];
        } else {
            $where = "$field >= $minTime";
        }
        return $where;
    }

    /**
     * 时间大于
     *
     * @param $field
     * @param $maxTime
     * @param bool $retarr
     * @return array|string|void
     */
    public static function maxTime($field, $maxTime, $retarr = true)
    {
        if (!$maxTime) return;
        $maxTime = trim($maxTime);
        if (!is_numeric($maxTime)) {
            if (strlen($maxTime) == 10) $maxTime .= ' 23:59:59';
            $maxTime = strtotime($maxTime);
        }

        if ($retarr) {
            $where[] = [$field, '<=', $maxTime];
        } else {
            $where = "{$field} <= $maxTime";
        }
        return $where;
    }

    /**
     * 模糊查询
     *
     * @param $field
     * @param $keywords
     * @param bool $retarr
     * @return array|string|void
     */
    public static function keywords($field, $keywords, $retarr = true)
    {
        $keywords = trim($keywords);
        if ($keywords === '') return;
        $keywords = preg_replace("/\s+/", '%', $keywords);

        if ($retarr) {
            $where[] = [$field, 'LIKE', "%$keywords%"];
        } else {
            $where = "{$field} LIKE %$keywords%";
        }
        return $where;
    }

    /**
     * 格式化search字段
     *
     * @param array $param
     * @param array $map ['search_key1','key2'=>'field2']
     * @param array $condition
     */
    public static function search(array $param, array $map, array &$condition = [])
    {
        if (!$param) {
            return;
        }

        foreach ($map as $key => $value) {
            $searchField = is_numeric($key) ? $value : $key;
            $field = is_numeric($key) ? substr($value, 7) : $value;
            if (isset($param[$searchField]) && $param[$searchField]) {
                $condition[$field] = ['like', '%' . $param[$searchField] . '%'];
            }
        }
    }

    /**
     * 格式化排序字段
     *
     * @param array $param ['sort_order1','order1'=>'field1']
     * @param array $map
     * @param $prefix string
     * @return bool|string
     */
    public static function order(array $param, array $map = [], $prefix = 'sort_')
    {
        if (!$param) {
            return false;
        }

        $order = '';
        // field = 'sort_field'
        if ($param) {
            foreach ($param as $field => $value) {
                if (!$value || $value == '-') {
                    continue;
                }
                if (!in_array($value, ['asc', 'desc'])) {
                    throw new ValidateException("{$field}的值必须在asc,desc中");
                }
                if (isset($map[$field])) {
                    $order .= "{$map[$field]} {$value},";
                } else {
                    $order .= substr($field, strlen($prefix)) . " {$value},";
                }
            }
        }

        if ($order) {
            return trim($order, ',');
        } else {
            return false;
        }
    }


    /**
     * 日期比较区间格式化
     *
     * @param array $param
     * @param string $field
     * @param array $map
     * @param array $condition
     */
    public static function dateInterval(array $param, $field, $map = ['start_at', 'end_at'], array &$condition)
    {
        $startAt = isset($param[$map[0]]) ? strtotime($param[$map[0]]) : false;
        $endAt = isset($param[$map[1]]) ? strtotime($param[$map[1]]) + 86400 : false;

        if ($startAt && $endAt) {
            $condition[$field] = ['between', [$startAt, $endAt]];
        } elseif ($startAt) {
            $condition[$field] = ['>=', $startAt];
        } elseif ($endAt) {
            $condition[$field] = ['<=', $endAt];
        }
    }


    /**
     * 格式化字段相等查询条件字段
     *
     * @param array $param
     * @param array $map
     * @param array $condition
     * @param boolean $explode 是否允许拆分,
     */
    public static function equal(array $param, array $map, array &$condition = [], $explode = true)
    {
        if (!$param) {
            return;
        }

        foreach ($map as $key => $value) {
            $searchField = is_numeric($key) ? $value : $key;
            $field = $value;

            if (isset($param[$searchField]) && $param[$searchField] !== '' && $param[$searchField] != '-') {
                $condition[$field] = self::parseInOrEqualWhere($param[$searchField], $explode);
            }
        }
    }

    /**
     * 自动解析等于或者数组或者带,的复杂条件组合 5.0版本
     *
     * @param string|array $value
     * @param boolean $explode 是否允许拆分,
     *
     * @return array
     */
    public static function parseInOrEqualWhere($value, $explode = true)
    {
        // 将 $value[0] 强制转为 $value
        if (is_array($value) && count($value) == 1 && is_scalar(reset($value))) {
            $value = reset($value);
        }
        // 将 id1,id2,id3 自动拆成数组
        if ($explode && is_scalar($value) && strpos($value, ',')) {
            $value = explode(',', $value);
        }

        return is_array($value) ? ['in', $value] : $value;
    }


    //----------------------------------------分割线，上面是原来的-----------------------------------------------------

    /**
     * 处理一个或者多个FIND_IN_SET字段查询查询条件
     * @param $param
     * @param $map
     * @param array $condition
     * @param string $logic
     * @param int $isNew
     * @param string $prefix
     * @param string $operateType
     */
    public static function parseFindInSet($param, $map, array &$condition, $logic = 'AND', $isNew = 0, $prefix = 'search_', $operateType = "find_in_set")
    {
        if (!$param) {
            return;
        }
        foreach ($map as $key => $value) {
            $searchField = is_numeric($key) ? $value : $key;
            $field = is_numeric($key) ? substr($value, strlen($prefix)) : $value;
            $sql = [];
            if (isset($param[$searchField]) && $param[$searchField]) {
                if (is_array($param[$searchField])) {
                    foreach ($param[$searchField] as $k => $val) {
                        if (!empty($val)) {
                            if ($operateType == 'find_in_set') {
                                $sql[] = " FIND_IN_SET('" . $val . "',$field) ";
                            } elseif ($operateType == '=') {
                                $sql[] = " $field = $val ";
                            }
                        }
                    }
                } elseif (is_string($param[$searchField]) || is_numeric($param[$searchField])) {
                    $array = explode(",", $param[$searchField]);
                    foreach ($array as $val) {

                        if (!empty($val)) {
                            if ($operateType == "find_in_set") {
                                $sql[] = " FIND_IN_SET('" . $val . "',$field) ";
                            } elseif ($operateType == '=') {
                                $sql[] = " $field = $val ";
                            }
                        }
                    }
                }
                if (!empty($sql)) {
                    if ($isNew == 0) {
                        $condition[] = ['exp', Db::raw("(" . implode($logic, $sql) . ")")];
                    } else {
                        $condition[] = ['', 'exp', Db::raw("(" . implode($logic, $sql) . ")")];
                    }
                }
            }
        }
    }

    /**
     * tp5.1构造find
     * @param $param
     * @param $map
     * @param array $condition
     * @param string $logic
     * @param string $operateType
     */
    public static function buildFindInSet($param, $map, array &$condition, $logic = 'AND', $operateType = "find_in_set")
    {
        self::parseFindInSet($param, $map, $condition, $logic, 1, "", $operateType);
    }

    /**
     * 构建order排序参数,支持多个排序 如果['id,desc','name,asc']
     * @param $orderParam
     * @param array $map
     * @return bool|string
     */
    public static function buildOrder($orderParam, $map = [])
    {

        $order = "";
        foreach ((array)$orderParam as $value) {

            list($field, $sort) = explode(",", $value);//
            if (!preg_match("/[a-zA-Z_]+/", $field)) { //过滤参数
                continue;
            }
            if (!in_array($sort, ['asc', 'desc'])) {
                continue;
            }
            if (isset($map[$field])) {
                $order .= "{$map[$field]} {$sort},";
            } else {
                $order .= $field . " {$sort},";
            }
        }

        if ($order) {
            return trim($order, ',');
        } else {
            return false;
        }
    }

    /**
     * 处理id的筛选条件
     * @param $ids
     * @param string $field
     * @return array
     */
    public static function parserIds($ids, $field = "id")
    {
        $condition = [];
        if (is_string($ids)) {
            $ids = explode(",", $ids);
            $condition[] = [$field, 'in', $ids];
        } elseif (is_array($ids)) {
            $condition[] = [$field, 'in', $ids];
        } elseif (is_numeric($ids)) {
            $condition[] = [$field, '=', $ids];

        }
        return $condition;
    }

    /**
     * 构造field查询字段
     * @param string $fields
     * @param string $prefix
     * @param string $extraFields
     * @param string $except
     * @return string
     */
    public static function buildFields($fields = "", string $prefix = "", $extraFields = "", string $except = "deleted_at,deleted_by")
    {
        if (is_string($fields)) {
            $fields = explode(",", $fields);
        }
        $extraFields = !empty($extraFields) ? array_unique(explode(",", $extraFields)) : [];
        $fields = array_unique($fields);
        $finalFields = [];
        $exceptFields = array_unique(explode(",", $except));
        foreach ($fields as &$field) {
            if (in_array($field, $exceptFields)) {
                continue;
            }
            if (!preg_match('/^([a-zA-Z]|_)+$/', $field) && $field != '*') {
                $finalFields[] = $field;
                continue;
            }
            $field = !empty($prefix) ? $prefix . "." . $field : $field;
            if (in_array($field, $extraFields)) {
                $extraFields = array_diff($extraFields, [$field]);
            }
            $finalFields[] = $field;
        }
        foreach ($extraFields as $extraField) {
            $finalFields[] = $extraField;
        }
        return implode(",", $finalFields);
    }


    /**
     * 构建id，或者逗号隔开的id
     * @param $ids
     * @param $pk
     * @param $condition
     * @return array
     */
    public static function buildIdsCondition($ids, $pk = 'id', array &$condition = [])
    {
        $where = [];
        if (is_numeric($ids)) {
            $where = [$pk, "=", $ids];
        } elseif (preg_match('/(\d+,?)+/', $ids)) {
            $where = [$pk, "in", explode(",", $ids)];
        } elseif (is_array($ids)) {
            $where = [$pk, "in", $ids];
        }
        if (!empty($where)) {
            $condition[] = $where;
        }
        return $condition;
    }


    /**
     * 时间戳区间构造条件
     * @param $timeValue
     * @param $field
     * @param string $symbol
     * @param array $condition
     * @return array
     */
    public static function timestampInterval($timeValue, $field, array &$condition = [], $symbol = ' ~ ')
    {
        $where = [];
        if (strpos($timeValue, trim($symbol))) {
            list($startAt, $endAt) = explode($symbol, $timeValue);
            if (is_numeric($startAt) || is_numeric($endAt)) {
                $startAt = (int)$startAt;
                if ($startAt == 0){
                    $where = [$field, "<", (int)$endAt];
                }else{
                    $where = [$field, "between", [(int)$startAt, (int)$endAt]];
                }
                $where[] = [$field, '<>', 0];
            } else {
                $where = [$field, "between", [(string)strtotime($startAt . "00:00:00"), (string)strtotime($endAt . "23:59:59")]];
            }
            $condition[] = $where;
        }
        return $where;
    }

    public static function explodeTime($value, $symbol = ' ~ ')
    {
        list($startAt, $endAt) = explode($symbol, $value);
        return [$startAt,$endAt];
    }

    /**
     * 搜索数据库的字段为date格式的时候区间搜索
     * @param $dataStr
     * @param $field
     * @param array $condition
     * @param string $symbol
     * @return array
     */
    public static function dateBetween($dataStr, $field, array &$condition = [], $symbol = '~')
    {
        $where = [];
        if (strpos($dataStr, $symbol)) {
            list($startAt, $endAt) = explode($symbol, $dataStr);
            if (preg_match('/\d{4}\-\d{2}\-\d{2}/', $startAt) && preg_match('/\d{4}\-\d{2}\-\d{2}/', $endAt)) {
                $where = [$field, "between", [$startAt, $endAt]];
                $condition[] = $where;
            }
        }
        return $where;
    }

    /**
     * 构建between条件
     * @param $value
     * @param string $filedName
     * @param array $condition
     * @param string $symbol
     * @return array
     */
    public static function between($value, $filedName = "", array &$condition = [], $symbol = ' ~ ')
    {
        $where = [];
        if (strpos($value, $symbol)) {
            list($from, $end) = explode(trim($symbol), $value);
            $where = [$filedName, 'between', [$from, $end]];
            $condition[] = $where;
        }
        return $where;
    }

    /**
     * p
     * @param $start
     * @param $end
     * @param string $symbol
     * @return string
     */
    public static function buildTimeRange($start, $end, $symbol = " ~ ")
    {
        return $start . "$symbol" . $end;
    }

    /**
     * 获取上一年的范围
     * @param $timeStr
     * @return string
     */
    public static function getLastYearRangeStr($timeStr)
    {
        $symbol = ' ~ ';
        list($start, $end) = explode($symbol, $timeStr);
        return implode($symbol, [$start - 365 * 24 * 3600,$end - 365 * 24 * 3600]);
    }

}
