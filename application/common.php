<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

if (!function_exists('exception')) {
    /**
     * 自定义异常处理类
     *
     * @param int $code 异常代码
     * @param string $message 异常值
     * @param string $exception 异常类
     */
    function exceptionHandle($message = '异常错误', $code = 1, $exception = '')
    {
        $e = $exception ?: '\app\common\exception\BaseException';
        throw new $e([
            'code' => $code,
            'message' => $message,
        ]);
    }
}