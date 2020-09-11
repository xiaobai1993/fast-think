<?php
/**
 *  xiaoxguo
 *  2018-12-09 13:35
 */

namespace app\common\lib;

use think\Model;

class DTO implements \ArrayAccess
{
    const FILTER_NULL = 1;
    const FILTER_EMPTY = 2;

    public static function create($array)
    {
        if ($array instanceof Model){
            $param = $array->toArray();
        }else{
            $param = $array;
        }
        return new static((array)$param);
    }

    public function __construct($arr = [])
    {
        if ($arr instanceof Model) {
            $arr = $arr->toArray();
        }
        if ($arr && is_array($arr)) {
            $this->setByArr($arr);
        }
    }

    public function toArr($type = self::FILTER_NULL, array $isAllow = [])
    {
        $arr = get_object_vars($this);
        if (!$type) {
            return $arr;
        }
        foreach ($arr as $k => $v) {
            if ($type == self::FILTER_NULL && is_null($v)) {
                unset($arr[$k]);
            }
            if ($type == self::FILTER_EMPTY && ($v === '' || is_null($v))) {
                unset($arr[$k]);
            }
        }
        if ($isAllow) {
            foreach ($arr as $k => $v) {
                if (!in_array($k, $isAllow)) {
                    unset($arr[$k]);
                }
            }
        }

        return $arr;
    }

    public function __get($name)
    {
        $cls = get_class($this);
        throw new \Exception("$cls.$name  no define!");
    }


    public function setByArr($arr)
    {
        foreach ($arr as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }

    static public function arrIns($arr)
    {
        $callCls = get_called_class();
        $dto = new $callCls;
        $dto->setByArr($arr);

        return $dto;
    }

    public static function getByBackend(array $param)
    {
        $dto = new static($param);

        if (isset($param['search_name_type'])) {
            $serchKey = $param['search_name_type'];
            $dto->$serchKey = $param['search_name'];
        }

        $dto->order = $dto->order ?
            $dto->order . $param['order_status'] : 'id desc';

        return $dto;
    }

    // ArrayAccess
    public function offsetExists($offset)
    {
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
    }
}