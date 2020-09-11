<?php
/**
 *  xiaoxguo
 *  2018-12-18 09:45
 */


namespace app\common\exception;

use think\Exception;

class BaseException extends Exception
{
    //Http状态吗
    public $code = 200;

    //错误信息
    public $message = '参数错误';

    //错误码
    public $errorCode = 10000;

    public function __construct($params = [])
    {
        if (!is_array($params)) {
            return;
        }
        if (array_key_exists('code', $params)) {
            $this->code = $params['code'];
        }
        if (array_key_exists('message', $params)) {
            $this->message = $params['message'];
        }
        if (array_key_exists('errorCode', $params)) {
            $this->errorCode = $params['errorCode'];
        }
    }
}