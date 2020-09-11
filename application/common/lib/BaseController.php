<?php
// +----------------------------------------------------------------------
// | [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010-2017 All rights reserved.
// +----------------------------------------------------------------------
// | Author: daydayin <huangminhu@gmail.com>
// +----------------------------------------------------------------------
// | Date: 2018/4/8 15:22
// +----------------------------------------------------------------------

namespace app\common\lib;

use app\common\helper\JsonStringHelper;
use think\Controller;


/**
 * Class BaseController
 * @package app\common\lib
 */
class BaseController extends Controller
{
    /**
     * 返回json格式数据
     *
     * @param array $data
     * @param string $msg
     * @param int $code
     * @return \think\Response\Json
     */
    protected function json($data = [], $msg = 'success', $code = 0)
    {
        return JsonStringHelper::jsonReturn($data, $msg, $code);
    }
}
