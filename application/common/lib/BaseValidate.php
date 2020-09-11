<?php
/**
 * Created by PhpStorm.
 * User: MOXIZW
 * Date: 2019/7/20
 * Time: 10:45
 */

namespace app\common\lib;

use think\facade\Request;
use think\Validate;

/**
 * Class BaseValidate
 * @package app\dbase\validate
 */
class BaseValidate extends Validate
{
    /**
     * @var
     */
    protected static $_instance;

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance[static::class])) {
            self::$_instance[static::class] = new static();
        }
        return static::$_instance[static::class];
    }

    /**
     * 验证方法
     * 1.获取所有值
     * 2.校验
     * 可以设定 如果是验证信息抛错 httpCode => 400
     *
     * @param array $param
     * @param string $scene
     * @return bool
     */
    public function goCheck($param = [], $scene = '')
    {
        if (empty($param)) {
            $request = Request::instance();
            $param = $request->param();
        }
        if (!$scene) {
            $scene = Request::action();// 获取当前方法名
        }
        if (array_key_exists($scene, $this->scene) == true) {
            $result = $this->scene($scene)->check($param);
        } else {
            $result = $this->check($param);
        }
        if (!$result) {
            $error = $this->error;
            exception($error);
        } else {
            return true;
        }
    }
}
