<?php
/**
 * Created by PhpStorm.
 * User: guodong
 * Date: 2018/10/17
 * Time: 下午2:45
 */

namespace app\common\lib;
/**
 * 枚举的基础类
 * Class BaseEnum
 * @package app\message\lib\enum
 */

abstract class BaseEnum
{
    private static $constCacheArray = NULL;

    protected static $MAP = [

    ];

    /**
     * 当前枚举对象的值
     * @var
     */
    private $enumVal;

    public function __construct($value = null)
    {
        if (is_numeric($value)){
            if (self::isValidValue($value)) {
                $this->enumVal = $value;
            }
        }elseif (is_string($value)){
            $constList = self::getConstants();
            if (self::isValidName($value)){
                $this->enumVal = $constList[$value];
            }
        }
    }

    /**
     * 设置枚举的值
     * @param $value
     */
    public function setEnumVal($value)
    {
        if (self::isValidValue($value)) {
            $this->enumVal = $value;
        }
    }

    /**
     * 暂时不删除
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if ($name = 'enumVal') {
            if (self::isValidValue($value)) {
                $this->enumVal = $value;
            }
        }
    }

    /**
     * 获取常量列表
     * @return mixed
     */
    private function getConstants()
    {
        if (self::$constCacheArray == NULL) {
            self::$constCacheArray = [];
        }
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect = new \ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }
        return self::$constCacheArray[$calledClass];
    }

    /**
     * 验证名称是否合法
     * @param $name
     * @param bool $strict
     * @return bool
     */
    public function isValidName($name, $strict = false)
    {
        $constants = self::getConstants();
        if ($strict) {
            return array_key_exists($name, $constants);
        }
        $keys = array_map('strtolower', array_keys($constants));
        return in_array(strtolower($name), $keys);
    }

    public function isValidValue($value, $strict = true)
    {
        $values = array_values(self::getConstants());
        return in_array($value, $values, $strict);
    }


    /**
     * 根据枚举值返回枚举对应的常量名称，用来动态实例化对象
     * @param $var
     * @return mixed
     */
    public function getConstName($var = null, $isThrow = false)
    {
        $var = $var === null ? $this->enumVal : $var;
        $constList = self::getConstants();
        foreach ($constList as $key => $value) {
            if ($value == $var) {
                return $key;
            }
        }
        if ($isThrow){
            exception(__CLASS__ . '不存在值为' . $var . '的枚举量', 100100);
        }else{
            return false;
        }
    }

    public function getEnumVal()
    {
        return $this->enumVal;
    }

    /**
     * 动态创建一个枚举对象
     * @param $var
     * @return static
     */
    public static function create($var)
    {
        return new static($var);
    }

    public static function name($value)
    {
        return self::$MAP[$value]??"";
    }
}